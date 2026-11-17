<?php

namespace App\Http\Controllers;

use App\Models\Booking;

use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function handle(Request $request)
    {
        // Log the incoming data for debugging
        \Log::info('Midtrans Callback Data: ', $request->all());

        $order_id = $request->input('order_id');
        $transaction_status = $request->input('transaction_status');

        // Verifikasi signature Midtrans (dikirim pada notifikasi HTTP).
        // Bila signature tidak ada (mis. tes manual), callback tetap diproses
        // tetapi dicatat di log.
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

        $booking = Booking::where('order_id', $order_id)->first();

        if (!$booking) {
            \Log::error('Booking not found for order_id: ' . $order_id);
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        if (in_array($transaction_status, ['settlement', 'capture'], true)) {
            // Pembayaran berhasil -> tiket siap divalidasi/di-check-in admin basecamp.
            if (in_array($booking->status, ['pending', 'cancelled'], true)) {
                $booking->status = 'confirmed';
                $booking->save();
            }

            \Log::info(
                'Pembayaran berhasil untuk booking ID: ' . $booking->id
            );
        } elseif (in_array($transaction_status, ['cancel', 'deny', 'expire'], true)) {
            // Jangan batalkan booking yang sudah check-in / selesai.
            if (!in_array($booking->status, ['completed'], true)) {
                $booking->status = 'cancelled';
                $booking->save();
            }

            \Log::info('Booking cancelled for order_id: ' . $order_id);
        } else {
            \Log::warning('Unhandled transaction status: ' . $transaction_status);
        }

        return response()->json([
            'message' => 'Callback processed',
            'status' => $booking->status
        ]);
    }
}
