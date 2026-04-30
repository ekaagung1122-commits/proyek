<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\Gunung;
use App\Models\AdminRequest;
use App\Models\GunungGaleri;
use App\Models\Booking;
use App\Models\Basecamp;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index() 
    {
        $userId = auth()->id();

        $gunungIds = Gunung::where('created_by', $userId = auth()->id())->latest()->get();

        return response()->json([
            'message' => 'Laporan Admin Gunung',
            'data' => [
                'total_gunung' => Gunung::where('created_by', $userId)->count(),
                'gunung_aktif' => Gunung::where('created_by', $userId)->where('status', 'aktif')->count(),
                'gunung_tidak_aktif' => Gunung::where('created_by', $userId)->where('status', 'tidak_aktif')->count(),
                'total_galeri' => GunungGaleri::whereIn('gunung_id', $gunungIds->pluck('id'))->count(),
                'total_request' => AdminRequest::where('request_by', $userId)->where('request_type', 'admin_basecamp')->count(),
            ]
        ]);
    }

    public function downloadPdf(Request $request) {
        $gunungIds = Gunung::where('created_by', auth()->id())->pluck('id');
        $basecampIds = Basecamp::whereIn('gunung_id', $gunungIds)->pluck('id');
        $query = Booking::with(['basecamp', 'user'])
        ->whereIn('basecamp_id', $basecampIds);

        if ($request->filled('from')) {
            $query->whereDate('tanggal_naik', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal_naik', '<=', $request->to);
        }

        $bookings = $query->latest()->get();

        $summary = [
            'total_gunung' => $gunungIds->count(),
            'total_basecamp' => $basecampIds->count(),
            'total_bookings' => $bookings->count(),
            'total_income' => $bookings
            ->whereIn('status', ['confirmed', 'completed'])
            ->sum('total_price'),
        ];

        $gunungData = Gunung::whereIn('id', $gunungIds)
        ->with('basecamps')
        ->get()
        ->map(function ($gunung) use ($bookings) {

            $basecampIds = $gunung->basecamps->pluck('id');

            $relatedBookings = $bookings->whereIn('basecamp_id', $basecampIds);

            return [
                'nama' => $gunung->nama,
                'basecamp_count' => $gunung->basecamps->count(),
                'booking_count' => $relatedBookings->count(),
                'income' => $relatedBookings
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->sum('total_price'),
            ];
        });

        $pdf = Pdf::loadView('pdf.admin-gunung-report', [
            'bookings' => $bookings,
            'summary' => $summary,
            'gunungData' => $gunungData,
            'user' => auth()->user(),
            'from' => $request->from,
            'to' => $request->to
        ]);
        
        return $pdf->download('laporan-admin-gunung.pdf');
    }
}
