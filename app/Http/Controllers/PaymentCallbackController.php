<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BasecampKuota;
use App\Models\Peralatan;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        \Log::info('Midtrans Callback Data: ', $request->all());

        $order_id = $request->input('order_id');
        $transaction_status = $request->input('transaction_status');

        $signature = $request->input('signature_key');
        $serverKey = env('MIDTRANS_SERVER_KEY');

        if ($signature && $serverKey) {
            $expected = hash(
                'sha512',
                $order_id
                . $request->input('status_code')
                . $request->input('gross_amount')
                . $serverKey
            );

            if (!hash_equals($expected, $signature)) {
                \Log::warning('Midtrans signature tidak valid untuk order_id: ' . $order_id);
                return response()->json([
                    'message' => 'Invalid signature'
                ], 403);
            }
        } elseif (!$signature) {
            \Log::warning('Callback Midtrans tanpa signature_key untuk order_id: ' . $order_id);
        }

        $booking = Booking::with('detailSewa')->where('order_id', $order_id)->first();

        if (!$booking) {
            \Log::error('Booking not found for order_id: ' . $order_id);
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if (in_array($transaction_status, ['settlement', 'capture'], true)) {
            if (in_array($booking->status, ['pending', 'cancelled'], true)) {
                $booking->status = 'confirmed';
                $booking->save();
            }
            \Log::info('Pembayaran berhasil untuk booking ID: ' . $booking->id);
        } elseif (in_array($transaction_status, ['cancel', 'deny', 'expire'], true)) {
            if (!in_array($booking->status, ['completed'], true)) {
                DB::transaction(function () use ($booking) {
                    $b = Booking::with('detailSewa.peralatan')->where('id', $booking->id)->lockForUpdate()->first();
                    if (!$b || $b->status !== 'pending') {
                        return;
                    }
                    $kuota = BasecampKuota::where('basecamp_id', $b->basecamp_id)
                        ->where('tanggal', $b->tanggal_naik)
                        ->lockForUpdate()
                        ->first();
                    if ($kuota && $kuota->kuota_terpakai >= $b->jumlah_pendaki) {
                        $kuota->kuota_terpakai -= $b->jumlah_pendaki;
                        $kuota->save();
                    }
                    foreach ($b->detailSewa as $ds) {
                        $alat = Peralatan::lockForUpdate()->find($ds->peralatan_id);
                        if ($alat) {
                            $alat->stok += (int) $ds->jumlah;
                            $alat->save();
                        }
                    }
                    $b->update(['status' => 'cancelled']);
                });
            }
            \Log::info('Booking cancelled for order_id: ' . $order_id);
        } else {
            \Log::warning('Unhandled transaction status: ' . $transaction_status);
        }

        return response()->json([
            'message' => 'Callback processed',
            'status' => $booking->fresh()->status
        ]);
    }
}
