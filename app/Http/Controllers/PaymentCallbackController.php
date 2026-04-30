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

        $booking = Booking::where('order_id', $order_id)->first();

        if (!$booking) {
            \Log::error('Booking not found for order_id: ' . $order_id);
            return response()->json([
                'message' => 'Booking not found'
                ], 404);
        }

        if ($transaction_status === 'settlement' || $transaction_status === 'capture') {
            $booking->status = 'confirmed';
            $booking->save();

            \Log::info('Booking confirmed for order_id: ' . $order_id);
        } elseif (in_array($transaction_status, ['cancel', 'deny', 'expire'])) {
            $booking->status = 'cancelled';
            $booking->save();

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
