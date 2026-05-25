<?php

namespace App\Http\Controllers\User;

use App\Models\Booking;
use App\Models\Basecamp;
use App\Models\BasecampKuota;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with('basecamp')
        ->where('user_id', auth()->id());

        if ($request->has('status')) {
            $bookings->where('status', $request->status);
        }

        $bookings = $bookings->latest()
        ->paginate(10)
        ->appends($request->query());

        return response()->json([
            'message' => 'Riwayat Booking',
            'data' => $bookings
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with('basecamp')
        ->where('user_id', auth()->id())
        ->where('id', $id)
        ->firstOrFail();

        return response()->json([
            'message' => 'Detail Booking',
            'data' => $booking
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'basecamp_id' => 'required|exists:basecamps,id',
            'tanggal_naik' => 'required|date',
            'jumlah_pendaki' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {

            $basecamp = Basecamp::findOrFail($request->basecamp_id);

            $duplicateBooking = Booking::where('user_id', auth()->id())
                ->where('tanggal_naik', $request->tanggal_naik)
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($duplicateBooking) {
                return response()->json([
                    'message' => 'Anda sudah punya booking aktif'
                ], 400);
            }

            if ($request->tanggal_naik < now()->toDateString()) {
                return response()->json([
                    'message' => 'Tanggal tidak valid'
                ], 400);
            }

            $kuota = BasecampKuota::where('basecamp_id', $basecamp->id)
                ->where('tanggal', $request->tanggal_naik)
                ->lockForUpdate()
                ->first();

            if (!$kuota) {
                return response()->json([
                    'message' => 'Kuota belum diatur'
                ], 400);
            }

            $sisaKuota = $kuota->kuota - $kuota->kuota_terpakai;

            if ($request->jumlah_pendaki > $sisaKuota) {
                return response()->json([
                    'message' => 'Kuota tidak mencukupi'
                ], 400);
            }

            $kuota->kuota_terpakai += $request->jumlah_pendaki;
            $kuota->save();

            $harga = $basecamp->harga_tiket;
            $total_price = $harga * $request->jumlah_pendaki;

            $booking = Booking::create([
                'user_id' => auth()->id(),
                'basecamp_id' => $request->basecamp_id,
                'tanggal_naik' => $request->tanggal_naik,
                'jumlah_pendaki' => $request->jumlah_pendaki,
                'harga_per_orang' => $harga,
                'total_price' => $total_price,
                'status' => 'pending',
            ]);

            // MIDTRANS
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$isProduction = false;
            \Midtrans\Config::$isSanitized = true;
            \Midtrans\Config::$is3ds = true;

            $orderId = 'BOOK-' . $booking->id . '-' . time();

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $booking->total_price,
                ],
                'customer_details' => [
                    'first_name' => auth()->user()->name,
                    'email' => auth()->user()->email
                ]
            ];

            $snapToken = \Midtrans\Snap::getSnapToken($params);

            $booking->update([
                'order_id' => $orderId,
                'snap_token' => $snapToken
            ]);

            activityLog(
                'create',
                'booking',
                'User booking ID ' . $booking->id
            );

            return response()->json([
                'message' => 'Booking berhasil',
                'data' => $booking->fresh(),
                'snap_token' => $snapToken
            ], 201);
        });
    }

    public function cancel($id)
    {
        $booking = Booking::where('user_id', auth()->id())
        ->where('id', $id)
        ->firstOrFail();

        if ($booking->status != 'pending') {
            return response()->json([
                'message' => 'Hanya booking dengan status pending yang bisa dibatalkan'
            ], 400);
        }

        $booking->update([
            'status' => 'cancelled'
        ]);

        activityLog(
            'cancel',
            'booking',
            'User cancel booking ID ' . $booking->id
        );

        return response()->json([
            'message' => 'Booking berhasil dibatalkan'
        ]);
    }

    public function downloadPdf($id)
    {
        $booking = Booking::with('basecamp')
        ->where('user_id', auth()->id())
        ->where('id', $id)
        ->firstOrFail();

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Hanya booking dengan status confirmed yang bisa diunduh tiketnya'
            ], 400);
        }

        $user = auth()->user();

        $pdf = Pdf::loadView('pdf.ticket', [
            'booking' => $booking,
            'user' => $user
        ]);

        return $pdf->download('ticket-booking-' . $booking->id . '.pdf');
    }

    public function history(Request $request)
    {
        $bookings = Booking::with('basecamp')
        ->where('user_id', auth()->id())
        ->whereIn('status', ['confirmed', 'completed']);

        if ($request->filled('status') && in_array($request->status, ['confirmed', 'completed'])) {
            $bookings->where('status', $request->status);
        }

        $bookings = $bookings->latest()
        ->paginate(10)
        ->appends($request->query());

        return response()->json([
            'message' => 'Riwayat Pendakian',
            'data' => $bookings
        ]);
    }

    public function reschedule(Request $request, $id)
    {
        $booking = Booking::where('user_id', auth()->id())
        ->where('id', $id)
        ->firstOrFail();

        $request->validate([
            'tanggal_naik' => 'required|date|after_or_equal:today',
        ]);

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'Hanya booking dengan status pending dan confirmed yang bisa dijadwal ulang'
            ], 400);
        }

        if ($booking->tanggal_naik == $request->tanggal_naik) {
            return response()->json([
                'message' => 'Tanggal naik baru tidak boleh sama dengan tanggal naik sebelumnya'
            ], 400);
        }

        $kuota = BasecampKuota::where('basecamp_id', $booking->basecamp_id)
        ->where('tanggal', $request->tanggal_naik)
        ->first();

        if (!$kuota) {
            return response()->json([
                'message' => 'Kuota untuk tanggal naik ini belum diatur'
            ], 400);
        }

        $totalBooked = Booking::where('basecamp_id', $booking->basecamp_id)
        ->where('tanggal_naik', $request->tanggal_naik)
        ->whereIn('status', ['pending', 'confirmed'])
        ->where('id', '!=', $booking->id)
        ->sum('jumlah_pendaki');

        $sisaKuota = $kuota->kuota - $totalBooked;

        if ($sisaKuota < $booking->jumlah_pendaki) {
            return response()->json([
                'message' => 'Kuota untuk tanggal naik ini tidak mencukupi'
            ], 400);
        }

        $booking->update([
            'tanggal_naik' => $request->tanggal_naik
        ]);

        return response()->json([
            'message' => 'Booking berhasil dijadwal ulang',
            'data' => $booking
        ]);
    }
}
