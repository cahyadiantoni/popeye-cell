<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Midtrans\Snap;
use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;
use Midtrans\Notification;
use App\Models\Faktur;
use App\Models\FakturPayment;
use App\Helpers\MidtransConfig;

class FakturPaymentController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            't_faktur_id' => 'required|exists:t_faktur,id',
            'amount' => 'required|integer|min:1000',
        ]);

        MidtransConfig::init();

        $faktur = Faktur::findOrFail($request->t_faktur_id);
        $order_id = 'INV-' . time() . '-' . rand(100, 999);

        $params = [
            'transaction_details' => [
                'order_id' => $order_id,
                'gross_amount' => $request->amount,
            ],
            'item_details' => [[
                'id' => $faktur->nomor_faktur,
                'price' => $request->amount,
                'quantity' => 1,
                'name' => 'Pembayaran Faktur #' . $faktur->nomor_faktur,
            ]],
            'customer_details' => [
                'first_name' => $faktur->pembeli ?? 'Pelanggan',
            ]
        ];

        $snapToken = Snap::getSnapToken($params);

        // Simpan data pembayaran ke DB
        FakturPayment::create([
            'order_id' => $order_id,
            't_faktur_id' => $faktur->id,
            'nomor_faktur' => $faktur->nomor_faktur,
            'amount' => $request->amount,
            'snap_token' => $snapToken,
        ]);

        return response()->json(['token' => $snapToken]);
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
            if (!in_array($notif->transaction_status, $finalStatuses)) {
                return response()->json(['message' => 'Ignored non-final status']);
            }

            // Cegah overwrite status jika sudah success
            if (in_array($payment->status, ['settlement', 'capture'])) {
                return response()->json(['message' => 'Already paid']);
            }

            $payment->update([
                'status' => $notif->transaction_status,
                'channel' => $notif->payment_type,
            ]);

            // Hitung ulang semua cicilan yang berhasil
            $totalCicilan = FakturPayment::where('t_faktur_id', $payment->t_faktur_id)
                ->whereIn('status', ['settlement', 'capture'])
                ->sum('amount');

            $faktur = $payment->faktur;
            if ($totalCicilan >= $faktur->total && !$faktur->is_lunas) {
                $faktur->update(['is_lunas' => 1]);
            }

            return response()->json(['message' => 'Callback processed']);
        } catch (\Exception $e) {
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json(['message' => 'Callback failed'], 500);
        }
    }

    public function retry(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:t_payments,order_id',
        ]);

        $payment = FakturPayment::where('order_id', $request->order_id)->first();

        if (!$payment) {
            return response()->json(['message' => 'Pembayaran tidak ditemukan'], 404);
        }

        // Ambil snap_token yang sudah disimpan
        $snapToken = $payment->snap_token;

        if (!$snapToken) {
            return response()->json(['message' => 'Token pembayaran tidak ditemukan'], 404);
        }

        return response()->json(['token' => $snapToken]);
    }

}
