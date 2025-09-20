<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model; // Import Model class

use App\Models\Faktur;
use App\Models\FakturOutlet;
use App\Models\KesimpulanBawah;
use App\Models\FakturPayment;
use App\Helpers\XenditConfig;

// Xendit v7 (OpenAPI)
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class FakturPaymentController extends Controller
{
    public function store(Request $request)
    {
        // Perubahan 1: Validasi diubah menjadi polymorphic
        $request->validate([
            'paymentable_id'   => 'required|integer',
            'paymentable_type' => 'required|string|in:faktur,faktur_outlet,kesimpulan_bawah',
            'amount'           => 'required|integer|min:1000',
            'payment_gateway'  => 'required|in:midtrans,xendit',
        ]);

        // Perubahan 2: Cari model induk secara dinamis
        $paymentable = $this->findPaymentable($request->paymentable_type, $request->paymentable_id);

        if (!$paymentable) {
            return response()->json(['message' => 'Data tidak ditemukan.'], 404);
        }

        $order_id = 'INV-' . time() . '-' . rand(100, 999);

        // Xendit
        return $this->createXenditPayment($request, $paymentable, $order_id);
    }

    /**
     * Helper untuk mencari model induk berdasarkan tipe dan ID.
     */
    private function findPaymentable(string $type, int $id): ?Model
    {
        $modelMap = [
            'faktur'           => Faktur::class,
            'faktur_outlet'    => FakturOutlet::class,
            'kesimpulan_bawah' => KesimpulanBawah::class,
        ];

        if (!isset($modelMap[$type])) {
            return null;
        }

        $modelClass = $modelMap[$type];
        return $modelClass::find($id);
    }

    // Perubahan 3: Method ini sekarang menerima Model generic, bukan Faktur spesifik
    private function createXenditPayment(Request $request, Model $paymentable, string $order_id)
    {
        XenditConfig::init();

        // Ambil nomor faktur/kesimpulan dan nama pembeli secara dinamis
        $nomor      = $paymentable->nomor_faktur ?? $paymentable->nomor_kesimpulan;
        $pembeli    = $paymentable->pembeli ?? 'Pelanggan';
        $itemType   = class_basename($paymentable); // e.g., "Faktur", "KesimpulanBawah"

        // Tentukan redirect URL secara dinamis
        $redirectRoute = '#'; // Default fallback
        if ($paymentable instanceof Faktur) {
            $redirectRoute = route('transaksi-faktur.show', $paymentable->nomor_faktur);
        }
        // Tambahkan kondisi lain jika FakturOutlet dan KesimpulanBawah punya route 'show'
        // else if ($paymentable instanceof FakturOutlet) {
        //     $redirectRoute = route('nama.route.outlet.show', $paymentable->nomor_faktur);
        // }

        $params = [
            'external_id'       => $order_id,
            'amount'            => (int) $request->amount,
            'description'       => "Pembayaran {$itemType} #{$nomor}",
            'invoice_duration'  => 86400,
            'currency'          => 'IDR',
            'customer'          => ['given_names' => $pembeli],
            'customer_notification_preference' => [
                'invoice_created'   => ['whatsapp', 'email', 'sms'],
                'invoice_reminder'  => ['whatsapp', 'email', 'sms'],
                'invoice_paid'      => ['whatsapp', 'email', 'sms'],
                'invoice_expired'   => ['whatsapp', 'email', 'sms'],
            ],
            'success_redirect_url' => $redirectRoute,
            'failure_redirect_url' => $redirectRoute,
        ];

        try {
            $api     = new InvoiceApi();
            $payload = new CreateInvoiceRequest($params);
            $invoice = $api->createInvoice($payload);

            $invoiceId   = $invoice->getId();
            $invoiceUrl  = $invoice->getInvoiceUrl();
            $invoiceStat = $invoice->getStatus();

            // Perubahan 4: Buat payment menggunakan relasi polymorphic
            $paymentable->payments()->create([
                'order_id'          => $order_id,
                'payment_gateway'   => 'xendit',
                'nomor_faktur'      => $nomor, // Kolom ini bisa dipertimbangkan untuk diganti nama atau dihapus jika tidak relevan lagi
                'amount'            => (int) $request->amount,
                'status'            => strtolower($invoiceStat),
                'xendit_invoice_id' => $invoiceId,
                'invoice_url'       => $invoiceUrl,
            ]);

            return response()->json(['invoice_url' => $invoiceUrl, 'gateway' => 'xendit']);
        } catch (\Throwable $e) {
            Log::error('Xendit Invoice Creation Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal membuat invoice Xendit'], 500);
        }
    }

    public function xenditCallback(Request $request)
    {
        $xenditCallbackToken = config('services.xendit.callback_verify_token');
        $receivedToken       = $request->header('x-callback-token');

        if (!hash_equals((string) $xenditCallbackToken, (string) $receivedToken)) {
            Log::warning('Xendit Callback: Invalid verification token.');
            return response()->json(['message' => 'Invalid token'], 403);
        }

        try {
            $payload = $request->all();
            Log::info('Xendit callback received', $payload);

            $payment = FakturPayment::where('order_id', $payload['external_id'] ?? '')->first();
            if (!$payment) {
                Log::warning('Xendit Callback: Order ID not found: ' . ($payload['external_id'] ?? '-'));
                return response()->json(['message' => 'Not found'], 404);
            }

            $incoming = strtoupper($payload['status'] ?? '');
            if ($incoming === 'PAID' || $incoming === 'SETTLED') {
                $status = 'settlement';
            } elseif ($incoming === 'EXPIRED') {
                $status = 'expire';
            } else {
                return response()->json(['message' => 'Ignoring status ' . $incoming]);
            }

            if (in_array($payment->status, ['settlement', 'capture'], true)) {
                return response()->json(['message' => 'Already paid']);
            }

            $payment->update([
                'status'  => $status,
                'channel' => $payload['payment_channel'] ?? 'Unknown Xendit Channel',
            ]);

            // Perubahan 5: Dapatkan model induk dan hitung total cicilan melalui relasi
            $parent = $payment->paymentable;
            if ($parent) {
                $totalCicilan = $parent->payments()
                    ->whereIn('status', ['settlement', 'capture'])
                    ->sum('amount');
                
                // Cek total yang harus dibayar (bisa 'total' atau 'grand_total')
                $totalToBePaid = $parent->grand_total ?? $parent->total;

                // Cek apakah model punya properti 'is_lunas' sebelum update
                if (property_exists($parent, 'is_lunas') && !$parent->is_lunas && $totalCicilan >= $totalToBePaid) {
                     $parent->update(['is_lunas' => 1]);
                }
            }

            return response()->json(['message' => 'Callback processed']);
        } catch (\Throwable $e) {
            Log::error('Xendit Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Callback failed'], 500);
        }
    }

    public function retry(Request $request)
    {
        // Method ini tidak perlu diubah karena hanya mencari berdasarkan order_id
        $request->validate([
            'order_id' => 'required|exists:t_payments,order_id',
        ]);

        $payment = FakturPayment::where('order_id', $request->order_id)->firstOrFail();

        if (!$payment->invoice_url) {
            return response()->json(['message' => 'URL Invoice tidak ditemukan'], 404);
        }
        return response()->json(['invoice_url' => $payment->invoice_url, 'gateway' => 'xendit']);
    }
}
