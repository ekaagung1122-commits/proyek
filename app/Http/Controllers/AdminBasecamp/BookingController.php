<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Booking;
use App\Models\TrackingSession;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'basecamp'])
            ->whereHas('basecamp', function ($query) {
                $query->where('admin_basecamp_id', auth()->id());
            });

        // Pencarian kode booking / nama / email pendaki.
        // Dilakukan di database supaya tiket lama (bukan hanya 10 data
        // terbaru) tetap bisa ditemukan saat validasi.
        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });

                if (is_numeric($search)) {
                    $q->orWhere('id', (int) $search);
                }

                // QR e-tiket bisa berisi kode fallback BOOK-{id}.
                if (preg_match('/^BOOK-(\d+)$/i', $search, $matches)) {
                    $q->orWhere('id', (int) $matches[1]);
                }
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $bookings = $query->latest()
            ->paginate(10)
            ->appends($request->query());

        return response()->json([
            'message' => 'Daftar Booking',
            'data' => $bookings
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'basecamp'])
            ->whereHas('basecamp', function ($query) {
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
            ->whereHas('basecamp', function ($query) {
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

        return response()->json([
            'message' => 'Check-in berhasil',
            'data' => $booking
        ]);
    }

    public function checkout($id)
    {
        $booking = Booking::with('basecamp')
            ->whereHas('basecamp', function ($query) {
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

        // Tutup sesi tracking yang masih aktif saat pendaki checkout.
        TrackingSession::where('booking_id', $booking->id)
            ->where('status', 'active')
            ->update([
                'status' => 'ended',
                'ended_at' => now(),
                'ended_reason' => 'checkout',
            ]);

        return response()->json([
            'message' => 'Check-out berhasil',
            'data' => $booking->fresh()
        ]);
    }

    public function confirm($id)
    {
        $booking = Booking::with('basecamp')
            ->whereHas('basecamp', function ($query) {
                $query->where('admin_basecamp_id', auth()->id());
            })
            ->where('id', $id)
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            return response()->json([
                'message' => 'Booking sudah diproses'
            ], 400);
        }

        $booking->update([
            'status' => 'confirmed'
        ]);

        return response()->json([
            'message' => 'Booking berhasil dikonfirmasi',
            'data' => $booking
        ]);
    }
}
