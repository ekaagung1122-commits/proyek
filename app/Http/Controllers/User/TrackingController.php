<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\TrackingPoint;
use App\Models\TrackingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TrackingController extends Controller
{
    /**
     * Konfigurasi tracking yang dipakai aplikasi mobile.
     */
    private function config(): array
    {
        return [
            'ping_interval_seconds' => 20,
            'geofence_radius_m' => 1000,
            'max_points_per_batch' => 100,
        ];
    }

    /**
     * Cek tiket yang sedang aktif (sudah check-in, belum checkout)
     * beserta sesi tracking aktif milik user.
     */
    public function active(Request $request)
    {
        $user = $request->user();

        $booking = Booking::with(['basecamp.gunung'])
            ->where('user_id', $user->id)
            ->whereNotNull('checkin_at')
            ->whereNull('checkout_at')
            ->latest('checkin_at')
            ->first();

        $session = TrackingSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        return response()->json([
            'message' => $booking
                ? 'Pendakian aktif ditemukan'
                : 'Tidak ada pendakian aktif',
            'data' => [
                'booking' => $booking,
                'session' => $session,
            ],
            'config' => $this->config(),
        ]);
    }

    /**
     * Mulai sesi tracking. Hanya boleh untuk tiket yang sudah
     * di-check-in oleh admin basecamp dan belum checkout.
     */
    public function start(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric|min:0',
            'battery' => 'nullable|integer|between:0,100',
            'client_uuid' => 'nullable|string|max:64',
            'recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();

        $booking = Booking::with('basecamp')
            ->where('user_id', $user->id)
            ->where('id', $validated['booking_id'])
            ->firstOrFail();

        if (!$booking->checkin_at) {
            return response()->json([
                'message' => 'Tiket belum divalidasi (check-in) oleh admin basecamp.',
            ], 422);
        }

        if ($booking->checkout_at) {
            return response()->json([
                'message' => 'Pendakian sudah selesai (checkout).',
            ], 422);
        }

        $existing = TrackingSession::where('booking_id', $booking->id)
            ->where('status', 'active')
            ->latest('started_at')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Tracking sudah aktif',
                'data' => [
                    'session' => $existing,
                    'booking' => $booking,
                    'already_active' => true,
                ],
                'config' => $this->config(),
            ]);
        }

        $otherActive = TrackingSession::where('user_id', $user->id)
            ->where('status', 'active')
            ->exists();

        if ($otherActive) {
            return response()->json([
                'message' => 'Masih ada sesi tracking lain yang aktif. Hentikan dahulu.',
            ], 409);
        }

        $recordedAt = isset($validated['recorded_at'])
            ? Carbon::parse($validated['recorded_at'])
            : now();

        $distance = $this->distanceMeters(
            $validated['latitude'],
            $validated['longitude'],
            $booking->basecamp?->latitude,
            $booking->basecamp?->longitude
        );

        $session = DB::transaction(function () use ($validated, $booking, $user, $recordedAt, $distance) {
            $session = TrackingSession::create([
                'booking_id' => $booking->id,
                'user_id' => $user->id,
                'basecamp_id' => $booking->basecamp_id,
                'status' => 'active',
                'started_at' => now(),
                'start_distance_m' => $distance,
                'last_latitude' => $validated['latitude'],
                'last_longitude' => $validated['longitude'],
                'last_accuracy' => $validated['accuracy'] ?? null,
                'last_altitude' => $validated['altitude'] ?? null,
                'last_speed' => $validated['speed'] ?? null,
                'last_battery' => $validated['battery'] ?? null,
                'last_recorded_at' => $recordedAt,
            ]);

            TrackingPoint::insertOrIgnore([[
                'tracking_session_id' => $session->id,
                'client_uuid' => $validated['client_uuid'] ?? ('start-' . $recordedAt->timestamp),
                'latitude' => $validated['latitude'],
                'longitude' => $validated['longitude'],
                'accuracy' => $validated['accuracy'] ?? null,
                'altitude' => $validated['altitude'] ?? null,
                'speed' => $validated['speed'] ?? null,
                'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
                'created_at' => now(),
            ]]);

            return $session;
        });

        return response()->json([
            'message' => 'Tracking dimulai',
            'data' => [
                'session' => $session->fresh(),
                'booking' => $booking,
                'already_active' => false,
            ],
            'config' => $this->config(),
        ], 201);
    }

    /**
     * Simpan batch titik GPS. Endpoint ini aman dipanggil ulang
     * karena setiap titik punya client_uuid unik.
     */
    public function ping(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:tracking_sessions,id',
            'battery' => 'nullable|integer|between:0,100',
            'points' => 'required|array|min:1|max:100',
            'points.*.client_uuid' => 'required|string|max:64',
            'points.*.latitude' => 'required|numeric|between:-90,90',
            'points.*.longitude' => 'required|numeric|between:-180,180',
            'points.*.accuracy' => 'nullable|numeric|min:0',
            'points.*.altitude' => 'nullable|numeric',
            'points.*.speed' => 'nullable|numeric|min:0',
            'points.*.recorded_at' => 'required|date',
        ]);

        $user = $request->user();

        $session = TrackingSession::where('id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($session->status !== 'active') {
            return response()->json([
                'message' => 'Sesi tracking sudah berakhir.',
                'ended' => true,
                'data' => $session,
            ], 409);
        }

        $rows = [];
        $latest = null;
        $lastRecordedAt = $session->last_recorded_at;

        // Abaikan titik yang sudah pernah tersimpan (pengiriman ulang dari
        // antrian offline) agar posisi terakhir tidak tertimpa data lama.
        $incomingUuids = array_map(
            fn ($point) => $point['client_uuid'],
            $validated['points']
        );

        $alreadySaved = TrackingPoint::where('tracking_session_id', $session->id)
            ->whereIn('client_uuid', $incomingUuids)
            ->pluck('client_uuid')
            ->flip();

        foreach ($validated['points'] as $point) {
            if ($alreadySaved->has($point['client_uuid'])) {
                continue;
            }

            $recordedAt = Carbon::parse($point['recorded_at']);

            $rows[] = [
                'tracking_session_id' => $session->id,
                'client_uuid' => $point['client_uuid'],
                'latitude' => $point['latitude'],
                'longitude' => $point['longitude'],
                'accuracy' => $point['accuracy'] ?? null,
                'altitude' => $point['altitude'] ?? null,
                'speed' => $point['speed'] ?? null,
                'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
                'created_at' => now(),
            ];

            if ($latest === null || $recordedAt->greaterThan($latest['recorded_at'])) {
                $latest = [
                    'recorded_at' => $recordedAt,
                    'latitude' => $point['latitude'],
                    'longitude' => $point['longitude'],
                    'accuracy' => $point['accuracy'] ?? null,
                    'altitude' => $point['altitude'] ?? null,
                    'speed' => $point['speed'] ?? null,
                ];
            }
        }

        if (empty($rows)) {
            return response()->json([
                'message' => 'Semua titik sudah tersimpan sebelumnya',
                'accepted' => 0,
                'ended' => false,
                'data' => $session,
            ]);
        }

        $accepted = DB::transaction(function () use ($rows, $latest, $session, $validated, $lastRecordedAt) {
            $inserted = TrackingPoint::insertOrIgnore($rows);

            $updates = [];

            // Hanya perbarui posisi terakhir bila titik ini memang lebih baru.
            // Ini mencegah data terakhir tertimpa saat antrian offline
            // mengirim ulang titik lama (client_uuid duplikat).
            if ($latest !== null
                && ($lastRecordedAt === null || $latest['recorded_at']->greaterThan($lastRecordedAt))) {
                $updates = [
                    'last_latitude' => $latest['latitude'],
                    'last_longitude' => $latest['longitude'],
                    'last_accuracy' => $latest['accuracy'],
                    'last_altitude' => $latest['altitude'],
                    'last_speed' => $latest['speed'],
                    'last_recorded_at' => $latest['recorded_at'],
                ];
            }

            if (array_key_exists('battery', $validated) && $validated['battery'] !== null) {
                $updates['last_battery'] = $validated['battery'];
            }

            if (!empty($updates)) {
                $session->update($updates);
            }

            return $inserted;
        });

        return response()->json([
            'message' => 'Titik tracking diterima',
            'accepted' => $accepted,
            'ended' => false,
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Hentikan sesi tracking dari aplikasi pendaki.
     */
    public function stop(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:tracking_sessions,id',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'altitude' => 'nullable|numeric',
            'speed' => 'nullable|numeric|min:0',
            'client_uuid' => 'nullable|string|max:64',
            'recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();

        $session = TrackingSession::where('id', $validated['session_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($session->status !== 'active') {
            return response()->json([
                'message' => 'Sesi tracking sudah berakhir',
                'data' => $session,
            ]);
        }

        $recordedAt = isset($validated['recorded_at'])
            ? Carbon::parse($validated['recorded_at'])
            : now();

        DB::transaction(function () use ($session, $validated, $recordedAt) {
            if (isset($validated['latitude'], $validated['longitude'])) {
                TrackingPoint::insertOrIgnore([[
                    'tracking_session_id' => $session->id,
                    'client_uuid' => $validated['client_uuid'] ?? ('stop-' . $recordedAt->timestamp),
                    'latitude' => $validated['latitude'],
                    'longitude' => $validated['longitude'],
                    'accuracy' => $validated['accuracy'] ?? null,
                    'altitude' => $validated['altitude'] ?? null,
                    'speed' => $validated['speed'] ?? null,
                    'recorded_at' => $recordedAt->format('Y-m-d H:i:s'),
                    'created_at' => now(),
                ]]);

                $session->last_latitude = $validated['latitude'];
                $session->last_longitude = $validated['longitude'];
                $session->last_accuracy = $validated['accuracy'] ?? $session->last_accuracy;
                $session->last_altitude = $validated['altitude'] ?? $session->last_altitude;
                $session->last_speed = $validated['speed'] ?? $session->last_speed;
                $session->last_recorded_at = $recordedAt;
            }

            $session->status = 'ended';
            $session->ended_at = now();
            $session->ended_reason = 'user';
            $session->save();
        });

        return response()->json([
            'message' => 'Tracking dihentikan',
            'data' => $session->fresh(),
        ]);
    }

    /**
     * Riwayat jejak milik user sendiri.
     */
    public function show(Request $request, $id)
    {
        $session = TrackingSession::with(['booking.basecamp', 'basecamp'])
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->firstOrFail();

        $points = $session->points()
            ->orderBy('recorded_at')
            ->limit(5000)
            ->get(['latitude', 'longitude', 'accuracy', 'altitude', 'speed', 'recorded_at']);

        return response()->json([
            'message' => 'Detail tracking',
            'data' => [
                'session' => $session,
                'points' => $points,
            ],
        ]);
    }

    /**
     * Jarak dua koordinat dalam meter (haversine).
     */
    private function distanceMeters($lat1, $lon1, $lat2, $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadius = 6371000;
        $dLat = deg2rad((float) $lat2 - (float) $lat1);
        $dLon = deg2rad((float) $lon2 - (float) $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad((float) $lat1)) * cos(deg2rad((float) $lat2)) * sin($dLon / 2) ** 2;

        return round($earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }
}
