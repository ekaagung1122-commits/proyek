<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TrackingSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    /**
     * Daftar pendaki yang sedang mendaki (sudah check-in, belum checkout).
     * Termasuk pendaki yang belum memulai tracking.
     */
    public function hikers(Request $request)
    {
        $user = $request->user();

        $query = Booking::with([
            'user:id,name,email,phone',
            'basecamp:id,nama,gunung_id,admin_basecamp_id,latitude,longitude',
            'basecamp.gunung:id,nama,created_by',
            'trackingSessions' => function ($q) {
                $q->where('status', 'active')->latest('started_at');
            },
        ])
            ->whereNotNull('checkin_at')
            ->whereNull('checkout_at');

        $this->applyBookingScope($query, $user);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('basecamp_id')) {
            $query->where('basecamp_id', $request->basecamp_id);
        }

        if ($request->filled('gunung_id')) {
            $query->whereHas('basecamp', function ($q) use ($request) {
                $q->where('gunung_id', $request->gunung_id);
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('tanggal_naik', $request->date);
        }

        if ($request->boolean('only_tracking')) {
            $query->whereHas('trackingSessions', function ($q) {
                $q->where('status', 'active');
            });
        }

        $bookings = $query->latest('checkin_at')->limit(300)->get();

        $data = $bookings->map(function (Booking $booking) {
            $session = $booking->trackingSessions->first();
            $basecamp = $booking->basecamp;
            $gunung = $basecamp?->gunung;

            return [
                'booking_id' => $booking->id,
                'kode_booking' => $booking->order_id ?: ('BOOK-' . $booking->id),
                'tanggal_naik' => $booking->tanggal_naik,
                'checkin_at' => $booking->checkin_at,
                'user' => [
                    'id' => $booking->user?->id,
                    'name' => $booking->user?->name,
                    'email' => $booking->user?->email,
                    'phone' => $booking->user?->phone,
                ],
                'basecamp' => $basecamp ? [
                    'id' => $basecamp->id,
                    'nama' => $basecamp->nama,
                    'latitude' => $basecamp->latitude,
                    'longitude' => $basecamp->longitude,
                ] : null,
                'gunung' => $gunung ? [
                    'id' => $gunung->id,
                    'nama' => $gunung->nama,
                ] : null,
                'has_tracking' => (bool) $session,
                'tracking' => $session ? [
                    'session_id' => $session->id,
                    'status' => $session->status,
                    'started_at' => $session->started_at,
                    'last_latitude' => $session->last_latitude,
                    'last_longitude' => $session->last_longitude,
                    'last_accuracy' => $session->last_accuracy,
                    'last_altitude' => $session->last_altitude,
                    'last_speed' => $session->last_speed,
                    'last_battery' => $session->last_battery,
                    'last_recorded_at' => $session->last_recorded_at,
                    'start_distance_m' => $session->start_distance_m,
                ] : null,
            ];
        })->values();

        return response()->json([
            'message' => 'Daftar pendaki aktif',
            'data' => $data,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Detail satu pendaki + jejak perjalanan.
     */
    public function showBooking(Request $request, $id)
    {
        $query = Booking::with([
            'user:id,name,email,phone',
            'basecamp.gunung',
            'members',
            'trackingSessions',
        ])->where('id', $id);

        $this->applyBookingScope($query, $request->user());

        $booking = $query->firstOrFail();

        $session = $booking->trackingSessions
            ->sortByDesc(function ($item) {
                return $item->status === 'active' ? 1 : 0;
            })
            ->sortByDesc('started_at')
            ->first();

        $points = collect();

        if ($session) {
            $points = $session->points()
                ->orderBy('recorded_at')
                ->get(['latitude', 'longitude', 'accuracy', 'altitude', 'speed', 'recorded_at']);

            // Batasi jumlah titik agar peta tetap ringan.
            if ($points->count() > 1200) {
                $step = (int) ceil($points->count() / 1000);
                $points = $points->filter(function ($point, $index) use ($step) {
                    return $index % $step === 0;
                })->values();
            }
        }

        return response()->json([
            'message' => 'Detail tracking pendaki',
            'data' => [
                'booking' => [
                    'id' => $booking->id,
                    'kode_booking' => $booking->order_id ?: ('BOOK-' . $booking->id),
                    'tanggal_naik' => $booking->tanggal_naik,
                    'jumlah_pendaki' => $booking->jumlah_pendaki,
                    'status' => $booking->status,
                    'checkin_at' => $booking->checkin_at,
                    'checkout_at' => $booking->checkout_at,
                    'user' => $booking->user,
                    'basecamp' => $booking->basecamp,
                    'members' => $booking->members,
                ],
                'session' => $session,
                'points' => $points,
            ],
        ]);
    }

    /**
     * Riwayat sesi tracking yang sudah selesai.
     */
    public function history(Request $request)
    {
        $user = $request->user();

        $query = TrackingSession::with([
            'booking:id,order_id,tanggal_naik,user_id,basecamp_id',
            'booking.user:id,name,email',
            'basecamp:id,nama,gunung_id,admin_basecamp_id',
            'basecamp.gunung:id,nama,created_by',
            'user:id,name,email',
        ])->where('status', 'ended');

        $this->applySessionScope($query, $user);

        if ($request->filled('basecamp_id')) {
            $query->where('basecamp_id', $request->basecamp_id);
        }

        if ($request->filled('gunung_id')) {
            $query->whereHas('basecamp', function ($q) use ($request) {
                $q->where('gunung_id', $request->gunung_id);
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('started_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('started_at', '<=', $request->to);
        }

        $sessions = $query->latest('started_at')->paginate(20);

        return response()->json([
            'message' => 'Riwayat tracking',
            'data' => $sessions,
        ]);
    }

    /**
     * Batasi query booking sesuai role admin.
     */
    private function applyBookingScope(Builder $query, $user): void
    {
        $roles = $user->roles()->pluck('name')->toArray();

        if (in_array('super_admin', $roles)) {
            return;
        }

        if (in_array('admin_gunung', $roles)) {
            $query->whereHas('basecamp.gunung', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
            return;
        }

        if (in_array('admin_basecamp', $roles)) {
            $query->whereHas('basecamp', function ($q) use ($user) {
                $q->where('admin_basecamp_id', $user->id);
            });
            return;
        }

        abort(403, 'Forbidden');
    }

    /**
     * Batasi query sesi tracking sesuai role admin.
     */
    private function applySessionScope(Builder $query, $user): void
    {
        $roles = $user->roles()->pluck('name')->toArray();

        if (in_array('super_admin', $roles)) {
            return;
        }

        if (in_array('admin_gunung', $roles)) {
            $query->whereHas('basecamp.gunung', function ($q) use ($user) {
                $q->where('created_by', $user->id);
            });
            return;
        }

        if (in_array('admin_basecamp', $roles)) {
            $query->whereHas('basecamp', function ($q) use ($user) {
                $q->where('admin_basecamp_id', $user->id);
            });
            return;
        }

        abort(403, 'Forbidden');
    }
}
