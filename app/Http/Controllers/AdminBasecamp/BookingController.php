<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index()
    {
         $bookings = Booking::with(['user', 'basecamp'])
        ->whereHas('basecamp', function($query) {
            $query->where('admin_basecamp_id', auth()->id());
        })
        ->latest()
        ->paginate(10);

        return response()->json([
            'message' => 'Daftar Booking',
            'data' => $bookings
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'basecamp'])
        ->whereHas('basecamp', function($query) {
            $query->where('admin_basecamp_id', auth()->id());
        })
        ->where('id', $id)
        ->firstOrFail();

        return response()->json([
            'message' => 'Detail Booking',
            'data' => $booking
        ]);
    }

    public function checkin(Request $request, $id)
    {
        $booking = Booking::with('basecamp')
        ->whereHas('basecamp', function($query) {
            $query->where('admin_basecamp_id', auth()->id());
        })
        ->where('id', $id)
        ->firstOrFail();

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Booking belum dikonfirmasi'
            ], 400);
        }

        if ($booking->checkin_at) {
            return response()->json([
                'message' => 'Booking sudah di-check-in'
            ], 400);
        }

        $booking->checkin_at = now();
        $booking->checkin_by = auth()->id();
        $booking->save();

        activityLog(
            'checkin',
            'booking',
            'Admin Basecamp checkin booking ID ' . $booking->id
        );

        return response()->json([
            'message' => 'Check-in berhasil',
            'data' => $booking
        ]);
    }

    public function checkout($id)
    {
        $booking = Booking::with('basecamp')
        ->whereHas('basecamp', function($query) {
            $query->where('admin_basecamp_id', auth()->id());
        })
        ->where('id', $id)
        ->firstOrFail();

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Booking belum dikonfirmasi'
            ], 400);
        }

        if (!$booking->checkin_at) {
            return response()->json([
                'message' => 'Pendaki belum check-in'
            ], 400);
        }

        if ($booking->checkout_at) {
            return response()->json([
                'message' => 'Pendaki sudah check-out'
            ], 400);
        }

        $booking->update([
            'checkout_at' => now(),
            'checkout_by' => auth()->id(),
            'status' => 'completed'
        ]);

        return response()->json([
            'message' => 'Check-out berhasil',
            'data' => $booking->fresh()
        ]);
    }
}