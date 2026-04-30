<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Booking;
use App\Models\Basecamp;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request) 
    {
        $basecampIds = Basecamp::where('admin_basecamp_id', auth()->id())->pluck('id');

        $query = Booking::with(['basecamp', 'user'])
        ->whereIn('basecamp_id', $basecampIds);

        if ($request->filled('from')) {
            $query->whereDate('tanggal_naik', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('tanggal_naik', '<=', $request->to);
        }

        return response()->json([
            'summary' => [
                'total_basecamp' => $basecampIds->count(),
                'total_bookings' => clone($query)->count(),
                'total_income' => clone($query)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->sum('total_price'),
            ],
            'bookings_by_status' => [
                'pending' => clone($query)->where('status', 'pending')->count(),
                'confirmed' => clone($query)->where('status', 'confirmed')->count(),
                'completed' => clone($query)->where('status', 'completed')->count(),
            ],
            'data' => clone($query)->latest()->paginate(10)  
        ]);
    }

    public function downloadPdf(Request $request) {
        $basecampIds = Basecamp::where('admin_basecamp_id', auth()->id())->pluck('id');
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
            'total_basecamp' => $basecampIds->count(),
            'total_bookings' => $bookings->count(),
            'total_income' => $bookings
                ->whereIn('status', ['confirmed', 'completed'])
                ->sum('total_price'),
        ];

        $pdf = Pdf::loadView('pdf.admin-basecamp-report', [
            'bookings' => $bookings,
            'summary' => $summary,
            'user' => auth()->user(),
        ]);

        return $pdf->download('laporan-admin-basecamp.pdf');
    }
}
