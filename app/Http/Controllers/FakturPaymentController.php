<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model; // Import Model class
use Illuminate\Support\Facades\DB;

use App\Models\Faktur;
use App\Models\FakturOutlet;
use App\Models\KesimpulanBawah;
use App\Models\FakturPayment;
use App\Models\LogWebhookXendit;
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

        // Ambil payload & jejak request
        $payload   = $request->all();
        $rawBody   = json_decode($request->getContent() ?: '[]', true);
        $headers   = $request->headers->all();
        $sourceIp  = $request->ip();

        // Siapkan data log (awal)
        $logData = [
            'event_id'            => $payload['id']           ?? null,
            'external_id'         => $payload['external_id']  ?? null,
            'payment_id'          => $payload['payment_id']   ?? null,
            'status'              => $payload['status']       ?? null,
            'amount'              => $payload['amount']       ?? null,
            'currency'            => $payload['currency']     ?? null,
            'payment_method'      => $payload['payment_method']     ?? null,
            'payment_channel'     => $payload['payment_channel']    ?? null,
            'bank_code'           => $payload['bank_code']          ?? null,
            'payment_destination' => $payload['payment_destination'] ?? null,
            'created_at_xendit'   => $payload['created']      ?? null,
            'updated_at_xendit'   => $payload['updated']      ?? null,
            'paid_at_xendit'      => $payload['paid_at']      ?? null,
            'success_redirect_url'=> $payload['success_redirect_url'] ?? null,
            'failure_redirect_url'=> $payload['failure_redirect_url'] ?? null,
            'raw_body'            => $rawBody ?: $payload, // fallback
            'headers'             => $headers,
            'source_ip'           => $sourceIp,
        ];

        try {
            Log::info('Xendit callback received', $payload);

            // Idempotensi: jika event_id sudah pernah tercatat, anggap selesai
            if (!empty($logData['event_id'])) {
                $existing = LogWebhookXendit::where('event_id', $logData['event_id'])->first();
                if ($existing) {
                    return response()->json(['message' => 'OK (duplicate ignored)']);
                }
            }

            // Simpan log awal (handled_result akan diupdate setelah proses)
            $log = LogWebhookXendit::create($logData);

            $incoming = strtoupper($payload['status'] ?? '');

            // Normalisasi status internal
            $mappedStatus = null;
            if (in_array($incoming, ['PAID', 'SETTLED'], true)) {
                $mappedStatus = 'settlement';
            } elseif ($incoming === 'EXPIRED') {
                $mappedStatus = 'expire';
            } else {
                // Simpan log sebagai ignored, lalu akhiri dengan 200
                $log->update([
                    'handled_result' => 'ignored',
                    'processing_note'=> 'Status not actionable: ' . ($incoming ?: '-'),
                ]);
                return response()->json(['message' => 'OK (ignored status)']);
            }

            // Cari payment berdasarkan external_id
            $payment = null;
            if (!empty($payload['external_id'])) {
                $payment = FakturPayment::where('order_id', $payload['external_id'])->first();
            }

            if (!$payment) {
                // TIDAK 404 —> balas 200 agar Xendit tidak retry, tapi catat
                Log::warning('Xendit Callback: Order ID not found: ' . ($payload['external_id'] ?? '-'));

                $log->update([
                    'handled_result' => 'not_found',
                    'processing_note'=> 'No FakturPayment matched external_id.',
                ]);

                return response()->json(['message' => 'OK (acknowledged, no mapping)']);
            }

            // Jika sudah paid, jangan ubah (idempoten)
            if (in_array($payment->status, ['settlement', 'capture'], true)) {
                $log->update([
                    'handled_result' => 'already_paid',
                    'processing_note'=> 'Payment already settled/captured.',
                ]);
                return response()->json(['message' => 'OK (already paid)']);
            }

            // Update payment + optionally parent (lunas)
            DB::transaction(function () use ($payment, $mappedStatus, $payload, $log) {
                $payment->update([
                    'status'  => $mappedStatus,
                    'channel' => $payload['payment_channel'] ?? 'Unknown Xendit Channel',
                ]);

                // Update parent jika semua cicilan >= total
                $parent = $payment->paymentable;
                if ($parent) {
                    $totalCicilan = $parent->payments()
                        ->whereIn('status', ['settlement', 'capture'])
                        ->sum('amount');

                    // Ambil total harus dibayar
                    $totalToBePaid = $parent->grand_total ?? $parent->total ?? null;

                    // Update is_lunas bila tersedia dan terpenuhi
                    if (!is_null($totalToBePaid) && $totalCicilan >= $totalToBePaid) {
                        // cek apakah kolom is_lunas memang ada di tabel parent
                        // gunakan try-catch ringan untuk amankan
                        try {
                            if (isset($parent->is_lunas) && !$parent->is_lunas) {
                                $parent->update(['is_lunas' => 1]);
                            }
                        } catch (\Throwable $e) {
                            // Jika parent tidak punya kolom is_lunas, cukup abaikan
                            Log::warning('Parent model has no is_lunas or update failed: ' . $e->getMessage());
                        }
                    }
                }

                $log->update([
                    'handled_result' => "updated:{$mappedStatus}",
                    'processing_note'=> 'Payment & (optional) parent updated.',
                ]);
            });

            return response()->json(['message' => 'OK (processed)']);
        } catch (\Throwable $e) {
            Log::error('Xendit Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // Upd. log jika sudah dibuat
            if (isset($log) && $log instanceof LogWebhookXendit) {
                $log->update([
                    'handled_result' => 'error',
                    'processing_note'=> $e->getMessage(),
                ]);
            } else {
                // Jika log belum sempat dibuat, coba simpan minimal crash note
                try {
                    LogWebhookXendit::create($logData + [
                        'handled_result' => 'error',
                        'processing_note'=> $e->getMessage(),
                    ]);
                } catch (\Throwable $ignored) {
                    // diamkan, jangan sampai balasan webhook ikut gagal
                }
            }

            // Balas 200 agar Xendit tidak terus retry (opsional bisa 500, tapi berisiko spam retry)
            return response()->json(['message' => 'OK (logged error)']);
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
