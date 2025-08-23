<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Faktur;
use App\Models\FakturPayment;

use App\Helpers\MidtransConfig;
use App\Helpers\XenditConfig;

// Midtrans
use Midtrans\Snap;
use Midtrans\Notification;

// Xendit v7 (OpenAPI)
use Xendit\Invoice\InvoiceApi;
use Xendit\Invoice\CreateInvoiceRequest;

class FakturPaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            't_faktur_id'     => 'required|exists:t_faktur,id',
            'amount'          => 'required|integer|min:1000',
            'payment_gateway' => 'required|in:midtrans,xendit',
        ]);

        // Midtrans init tetap dipanggil, aman walau gateway xendit
        MidtransConfig::init();

        $faktur   = Faktur::findOrFail($request->t_faktur_id);
        $order_id = 'INV-' . time() . '-' . rand(100, 999);

        if ($request->payment_gateway === 'midtrans') {
            return $this->createMidtransPayment($request, $faktur, $order_id);
        }

        // Xendit
        return $this->createXenditPayment($request, $faktur, $order_id);
    }

    private function createMidtransPayment(Request $request, Faktur $faktur, string $order_id)
    {
        MidtransConfig::init();

        $params = [
            'transaction_details' => [
                'order_id'      => $order_id,
                'gross_amount'  => (int) $request->amount,
            ],
            'item_details' => [[
                'id'       => $faktur->nomor_faktur,
                'price'    => (int) $request->amount,
                'quantity' => 1,
                'name'     => 'Pembayaran Faktur #' . $faktur->nomor_faktur,
            ]],
            'customer_details' => [
                'first_name' => $faktur->pembeli ?? 'Pelanggan',
            ],
        ];

        $snapToken = Snap::getSnapToken($params);

        FakturPayment::create([
            'order_id'        => $order_id,
            'payment_gateway' => 'midtrans',
            't_faktur_id'     => $faktur->id,
            'nomor_faktur'    => $faktur->nomor_faktur,
            'amount'          => (int) $request->amount,
            'snap_token'      => $snapToken,
            'status'          => 'pending',
        ]);

        return response()->json(['token' => $snapToken, 'gateway' => 'midtrans']);
    }

    private function createXenditPayment(Request $request, Faktur $faktur, string $order_id)
    {
        // Pastikan helper ini memanggil:
        // \Xendit\Configuration::setXenditKey(config('services.xendit.secret_key'));
        XenditConfig::init();

        $params = [
            'external_id'      => $order_id,
            'amount'           => (int) $request->amount,
            'description'      => 'Pembayaran Faktur #' . $faktur->nomor_faktur,
            'invoice_duration' => 86400, // 24 jam
            'currency'         => 'IDR',
            'customer'         => [
                'given_names' => $faktur->pembeli ?? 'Pelanggan',
            ],
            'customer_notification_preference' => [
                'invoice_created' => ['whatsapp', 'email', 'sms'],
                'invoice_reminder' => ['whatsapp', 'email', 'sms'],
                'invoice_paid' => ['whatsapp', 'email', 'sms'],
                'invoice_expired' => ['whatsapp', 'email', 'sms'],
            ],
            'success_redirect_url' => route('transaksi-faktur.show', $faktur->nomor_faktur),
            'failure_redirect_url' => route('transaksi-faktur.show', $faktur->nomor_faktur),
        ];

        try {
            $api     = new InvoiceApi();
            $payload = new CreateInvoiceRequest($params);
            $invoice = $api->createInvoice($payload);

            // Respons SDK v7 biasanya object model dengan getter.
            $invoiceId   = method_exists($invoice, 'getId') ? $invoice->getId() : ($invoice['id'] ?? null);
            $invoiceUrl  = method_exists($invoice, 'getInvoiceUrl') ? $invoice->getInvoiceUrl() : ($invoice['invoice_url'] ?? null);
            $invoiceStat = method_exists($invoice, 'getStatus') ? $invoice->getStatus() : ($invoice['status'] ?? 'PENDING');

            FakturPayment::create([
                'order_id'         => $order_id,
                'payment_gateway'  => 'xendit',
                't_faktur_id'      => $faktur->id,
                'nomor_faktur'     => $faktur->nomor_faktur,
                'amount'           => (int) $request->amount,
                'status'           => strtolower($invoiceStat), // biasanya "PENDING"
                'xendit_invoice_id'=> $invoiceId,
                'invoice_url'      => $invoiceUrl,
            ]);

            return response()->json(['invoice_url' => $invoiceUrl, 'gateway' => 'xendit']);
        } catch (\Throwable $e) {
            Log::error('Xendit Invoice Creation Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Gagal membuat invoice Xendit'], 500);
        }
    }

    public function callback(Request $request)
    {
        try {
            MidtransConfig::init();
            $notif = new Notification();

            Log::info('Midtrans callback received', (array) $notif);

            $payment = FakturPayment::where('order_id', $notif->order_id)->first();
            if (!$payment) {
                Log::warning('Order ID not found: ' . $notif->order_id);
                return response()->json(['message' => 'Not found'], 404);
            }

            $finalStatuses = ['settlement', 'capture', 'cancel', 'deny', 'expire'];
            if (!in_array($notif->transaction_status, $finalStatuses, true)) {
                return response()->json(['message' => 'Ignored non-final status']);
            }

            if (in_array($payment->status, ['settlement', 'capture'], true)) {
                return response()->json(['message' => 'Already paid']);
            }

            $payment->update([
                'status'  => $notif->transaction_status,
                'channel' => $notif->payment_type,
            ]);

            $totalCicilan = FakturPayment::where('t_faktur_id', $payment->t_faktur_id)
                ->whereIn('status', ['settlement', 'capture'])
                ->sum('amount');

            $faktur = $payment->faktur;
            if ($totalCicilan >= $faktur->total && !$faktur->is_lunas) {
                $faktur->update(['is_lunas' => 1]);
            }

            return response()->json(['message' => 'Callback processed']);
        } catch (\Throwable $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Callback failed'], 500);
        }
    }

    public function xenditCallback(Request $request)
    {
        // Verifikasi token dari header
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

            // Map status Xendit -> internal
            $incoming = strtoupper($payload['status'] ?? '');
            if ($incoming === 'PAID') {
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

            $totalCicilan = FakturPayment::where('t_faktur_id', $payment->t_faktur_id)
                ->whereIn('status', ['settlement', 'capture'])
                ->sum('amount');

            $faktur = $payment->faktur;
            if ($totalCicilan >= $faktur->total && !$faktur->is_lunas) {
                $faktur->update(['is_lunas' => 1]);
            }

            return response()->json(['message' => 'Callback processed']);
        } catch (\Throwable $e) {
            Log::error('Xendit Callback Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Callback failed'], 500);
        }
    }

    public function retry(Request $request)
    {
        $request->validate([
            // Ganti dengan nama tabelmu yang benar bila berbeda
            'order_id' => 'required|exists:t_payments,order_id',
        ]);

        $payment = FakturPayment::where('order_id', $request->order_id)->firstOrFail();

        if ($payment->payment_gateway === 'midtrans') {
            if (!$payment->snap_token) {
                return response()->json(['message' => 'Token pembayaran tidak ditemukan'], 404);
            }
            return response()->json(['token' => $payment->snap_token, 'gateway' => 'midtrans']);
        }

        if ($payment->payment_gateway === 'xendit') {
            if (!$payment->invoice_url) {
                return response()->json(['message' => 'URL Invoice tidak ditemukan'], 404);
            }
            return response()->json(['invoice_url' => $payment->invoice_url, 'gateway' => 'xendit']);
        }

        return response()->json(['message' => 'Gateway pembayaran tidak valid'], 400);
    }
}
