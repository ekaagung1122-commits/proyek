<?php

namespace App\Http\Controllers\User;

use App\Models\Booking;
use App\Models\Basecamp;
use App\Models\BasecampKuota;
use App\Models\BookingMember;
use App\Models\Peralatan;
use App\Models\Promo;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $bookings = Booking::with(['basecamp', 'detailSewa.peralatan'])
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
        $booking = Booking::with(['basecamp', 'detailSewa.peralatan', 'promo', 'members'])
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
            'anggota' => 'required|array|min:1',
            'anggota.*.nama' => 'required|string|max:255',
            'anggota.*.alamat' => 'required|string',
            'anggota.*.no_hp' => 'nullable|string|max:30',
            'anggota.*.tanggal_lahir' => 'required|date',
            'anggota.*.jenis_kelamin' => 'required|in:L,P',
            'anggota.*.identitas' => 'required|string|max:50',
            'anggota.*.foto_identitas' => 'nullable|file|image|max:2048',
        ]);

        try {
            return DB::transaction(function () use ($request) {
                $basecamp = Basecamp::findOrFail($request->basecamp_id);

                $duplicateBooking = Booking::where('user_id', auth()->id())
                    ->where('tanggal_naik', $request->tanggal_naik)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->exists();

                if ($duplicateBooking) {
                    throw new \Exception('Anda sudah punya booking aktif untuk tanggal ini.');
                }

                if ($request->tanggal_naik < now()->toDateString()) {
                    throw new \Exception('Tanggal tidak valid atau sudah lewat.');
                }

                $kuota = BasecampKuota::where('basecamp_id', $basecamp->id)
                    ->where('tanggal', $request->tanggal_naik)
                    ->lockForUpdate()
                    ->first();

                if (!$kuota) {
                    throw new \Exception('Kuota belum diatur oleh admin.');
                }

                $sisaKuota = $kuota->kuota - $kuota->kuota_terpakai;
                if ((int) $request->jumlah_pendaki > $sisaKuota) {
                    throw new \Exception('Kuota untuk tanggal ini tidak mencukupi.');
                }

                $kuota->kuota_terpakai += (int) $request->jumlah_pendaki;
                $kuota->save();

                $hargaTiketSatuan = (int) ($basecamp->harga_tiket ?? 0);
                $totalHargaTiket = $hargaTiketSatuan * (int) $request->jumlah_pendaki;
                if ($totalHargaTiket <= 0) {
                    $totalHargaTiket = (int) $request->input('total_price', 50000);
                    $hargaTiketSatuan = (int) round($totalHargaTiket / (int) $request->jumlah_pendaki);
                }

                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'basecamp_id' => $request->basecamp_id,
                    'tanggal_naik' => $request->tanggal_naik,
                    'jumlah_pendaki' => (int) $request->jumlah_pendaki,
                    'harga_per_orang' => $hargaTiketSatuan,
                    'total_sewa_alat' => 0,
                    'promo_id' => null,
                    'total_price' => $totalHargaTiket,
                    'status' => 'pending',
                    'checkout_by' => auth()->id(),
                ]);

                if ($request->has('anggota') && is_array($request->anggota)) {
                    foreach ($request->anggota as $index => $anggota) {
                        $fotoPath = null;
                        if ($request->hasFile("anggota.$index.foto_identitas")) {
                            $fotoPath = $request
                                ->file("anggota.$index.foto_identitas")
                                ->store('identitas', 'public');
                        }

                        BookingMember::create([
                            'booking_id' => $booking->id,
                            'nama' => $anggota['nama'],
                            'alamat' => $anggota['alamat'],
                            'tanggal_lahir' => $anggota['tanggal_lahir'],
                            'jenis_kelamin' => $anggota['jenis_kelamin'],
                            'identitas' => $anggota['identitas'],
                            'no_hp' => $anggota['no_hp'] ?? null,
                            'foto_identitas' => $fotoPath,
                        ]);
                    }
                }

                return response()->json([
                    'message' => 'Booking berhasil',
                    'data' => [
                        'id' => $booking->id,
                        'kode_booking' => $booking->kode_booking,
                        'basecamp_id' => $booking->basecamp_id,
                        'jumlah_pendaki' => $booking->jumlah_pendaki,
                        'total_price' => $booking->total_price,
                    ],
                ], 201);
            });
        } catch (\Exception $e) {
            \Log::error('Gagal Booking: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function cancel($id)
    {
        $booking = Booking::with('detailSewa.peralatan')
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if ($booking->status != 'pending') {
            return response()->json([
                'message' => 'Hanya booking dengan status pending yang bisa dibatalkan'
            ], 400);
        }

        DB::transaction(function () use ($booking) {
            $kuota = BasecampKuota::where('basecamp_id', $booking->basecamp_id)
                ->where('tanggal', $booking->tanggal_naik)
                ->lockForUpdate()
                ->first();
            if ($kuota && $kuota->kuota_terpakai >= $booking->jumlah_pendaki) {
                $kuota->kuota_terpakai -= $booking->jumlah_pendaki;
                $kuota->save();
            }

            foreach ($booking->detailSewa as $ds) {
                $alat = Peralatan::lockForUpdate()->find($ds->peralatan_id);
                if ($alat) {
                    $alat->stok += (int) $ds->jumlah;
                    $alat->save();
                }
            }

            $booking->update(['status' => 'cancelled']);
        });

        if (function_exists('activityLog')) {
            activityLog('cancel', 'booking', 'User cancel booking ID ' . $booking->id);
        }

        return response()->json(['message' => 'Booking berhasil dibatalkan']);
    }

    public function pay(Request $request, $id)
    {
        $booking = Booking::with('detailSewa.peralatan')
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking ini tidak dapat dibayar.'], 400);
        }

        $request->validate([
            'items' => 'nullable|array',
            'items.*.peralatan_id' => 'required|integer|exists:peralatans,id',
            'items.*.jumlah' => 'required|integer|min:1',
        ]);

        try {
            $result = DB::transaction(function () use ($request, $booking) {
                $booking->refresh();
                $booking = Booking::with('detailSewa.peralatan')
                    ->where('id', $booking->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($booking->status !== 'pending') {
                    throw new \Exception('Booking ini tidak dapat dibayar.');
                }

                $items = $request->input('items');

                // Jika ada items baru dari request, replace detailSewa
                if (is_array($items) && count($items) > 0) {
                    // kembalikan stok lama
                    foreach ($booking->detailSewa as $ds) {
                        $alat = Peralatan::lockForUpdate()->find($ds->peralatan_id);
                        if ($alat) {
                            $alat->stok += (int) $ds->jumlah;
                            $alat->save();
                        }
                    }
                    $booking->detailSewa()->delete();
                    $booking->refresh();

                    $totalSewaAlat = 0;
                    foreach ($items as $eq) {
                        $alat = Peralatan::lockForUpdate()->findOrFail($eq['peralatan_id']);
                        if ($alat->basecamp_id != $booking->basecamp_id) {
                            throw new \Exception('Peralatan tidak sesuai dengan basecamp booking.');
                        }
                        if ((int) $alat->stok < (int) $eq['jumlah']) {
                            throw new \Exception("Stok {$alat->nama_alat} tidak mencukupi. Sisa: {$alat->stok}");
                        }
                        $subtotalAlat = (int) $alat->harga_sewa * (int) $eq['jumlah'];
                        $totalSewaAlat += $subtotalAlat;
                        $booking->detailSewa()->create([
                            'peralatan_id' => $alat->id,
                            'jumlah' => (int) $eq['jumlah'],
                            'harga_satuan' => (int) $alat->harga_sewa,
                            'subtotal' => $subtotalAlat,
                        ]);
                        $alat->decrement('stok', (int) $eq['jumlah']);
                    }
                    $booking->total_sewa_alat = $totalSewaAlat;
                } else {
                    // gunakan detailSewa yang sudah ada (retry dari Riwayat)
                    if ($booking->detailSewa->count() === 0) {
                        $booking->total_sewa_alat = 0;
                    } else {
                        $booking->total_sewa_alat = (int) $booking->detailSewa->sum('subtotal');
                    }
                }

                $totalTiket = (int) ($booking->harga_per_orang * $booking->jumlah_pendaki);
                $grandTotal = $totalTiket + (int) $booking->total_sewa_alat;
                $grandTotal = max(0, $grandTotal);

                $booking->total_price = $grandTotal;
                $booking->save();

                $orderId = 'BOOK-' . $booking->id . '-' . time();
                $user = auth()->user();
                $userName = $user && $user->name ? $user->name : 'Pendaki';
                $userEmail = $user && $user->email ? $user->email : 'pendaki@summitgo.com';

                $serverKey = env('MIDTRANS_SERVER_KEY');
                $isProd = env('MIDTRANS_IS_PRODUCTION', false);
                $midtransUrl = $isProd
                    ? 'https://app.midtrans.com/snap/v1/transactions'
                    : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

                $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
                $payload = [
                    'transaction_details' => [
                        'order_id' => $orderId,
                        'gross_amount' => (int) $grandTotal,
                    ],
                    'customer_details' => [
                        'first_name' => strval($userName),
                        'email' => strval($userEmail),
                    ],
                    'callbacks' => [
                        'finish' => $frontendUrl . '/riwayat?payment=finish&booking=' . $booking->id,
                    ],
                ];

                $response = Http::withoutVerifying()
                    ->withBasicAuth($serverKey, '')
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])
                    ->post($midtransUrl, $payload);

                if ($response->failed()) {
                    throw new \Exception('Midtrans API Error: ' . $response->body());
                }

                $midtransData = $response->json();
                $snapToken = $midtransData['token'] ?? null;
                $redirectUrl = $midtransData['redirect_url'] ?? null;

                if (!$snapToken) {
                    throw new \Exception('Gagal mendapatkan Snap Token dari Midtrans.');
                }

                $booking->update([
                    'order_id' => $orderId,
                    'snap_token' => $snapToken,
                ]);

                return [
                    'booking' => $booking->fresh(['basecamp', 'detailSewa.peralatan']),
                    'snap_token' => $snapToken,
                    'redirect_url' => $redirectUrl,
                ];
            });

            return response()->json([
                'message' => 'Silakan lanjutkan pembayaran',
                'data' => $result['booking'],
                'snap_token' => $result['snap_token'],
                'redirect_url' => $result['redirect_url'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Gagal Pay Booking: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function paymentStatus($id)
    {
        $booking = Booking::where('user_id', auth()->id())
            ->where('id', $id)
            ->firstOrFail();

        if (!$booking->order_id) {
            return response()->json(['message' => 'Booking belum memiliki transaksi pembayaran.'], 400);
        }

        $serverKey = env('MIDTRANS_SERVER_KEY');
        $isProd = env('MIDTRANS_IS_PRODUCTION', false);
        $baseUrl = $isProd ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        $url = $baseUrl . '/v2/' . urlencode($booking->order_id) . '/status';

        try {
            $response = Http::withoutVerifying()
                ->withBasicAuth($serverKey, '')
                ->get($url);

            if ($response->failed()) {
                return response()->json(['message' => 'Gagal mengambil status pembayaran.'], 400);
            }

            $data = $response->json();
            $txStatus = $data['transaction_status'] ?? null;

            if (in_array($txStatus, ['settlement', 'capture'], true)) {
                if ($booking->status === 'pending') {
                    $booking->update(['status' => 'confirmed']);
                }
            } elseif (in_array($txStatus, ['cancel', 'deny', 'expire'], true)) {
                if ($booking->status === 'pending') {
                    // rollback kuota & stok
                    DB::transaction(function () use ($booking) {
                        $booking->refresh();
                        $kuota = BasecampKuota::where('basecamp_id', $booking->basecamp_id)
                            ->where('tanggal', $booking->tanggal_naik)
                            ->lockForUpdate()
                            ->first();
                        if ($kuota && $kuota->kuota_terpakai >= $booking->jumlah_pendaki) {
                            $kuota->kuota_terpakai -= $booking->jumlah_pendaki;
                            $kuota->save();
                        }
                        $booking->load('detailSewa.peralatan');
                        foreach ($booking->detailSewa as $ds) {
                            $alat = Peralatan::lockForUpdate()->find($ds->peralatan_id);
                            if ($alat) {
                                $alat->stok += (int) $ds->jumlah;
                                $alat->save();
                            }
                        }
                        $booking->update(['status' => 'cancelled']);
                    });
                }
            }

            return response()->json([
                'message' => 'OK',
                'transaction_status' => $txStatus,
                'booking_status' => $booking->fresh()->status,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
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