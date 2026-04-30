<?php

namespace App\Http\Controllers\AdminBasecamp;

use App\Models\Booking;
use App\Models\Basecamp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $basecampIds = Basecamp::where('admin_basecamp_id', auth()->id())->pluck('id');
        $bookingQuery = Booking::whereIn('basecamp_id', $basecampIds);

        return response()->json([
            'message' => 'Dashboard Admin Basecamp',
            'data' => [
                'total_basecamp' => $basecampIds->count(),
                'total_bookings' => (clone $bookingQuery)->count(),
                'total_income' => (clone $bookingQuery)
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->sum('total_price'),
                'confirmed_bookings' => (clone $bookingQuery)
                    ->where('status', 'confirmed')
                    ->count(),
                'completed_bookings' => (clone $bookingQuery)
                    ->where('status', 'completed')
                    ->count(),
                'pending_bookings' => (clone $bookingQuery)
                    ->where('status', 'pending')
                    ->count(),
                'checkin' => (clone $bookingQuery)
                    ->whereNotNull('checkin_at')
                    ->whereNull('checkout_at')
                    ->count(),
                'pendaki_aktif' => (clone $bookingQuery)
                    ->whereDate('tanggal_naik', now()->toDateString())
                    ->whereIn('status', ['confirmed', 'completed'])
                    ->sum('jumlah_pendaki')   
            ]
        ]);
    }

    public function chart()
    {
        $basecampIds = Basecamp::where('admin_basecamp_id', auth()->id())->pluck('id');

        $incomeByMonth = Booking::whereIn('basecamp_id', $basecampIds)
            ->whereIn('status', ['confirmed', 'completed'])
            ->selectRaw('MONTH(created_at) as month, SUM(total_price) as total_income')
            ->groupBy('month')
            ->get()
            ->pluck('total_income', 'month');

        $bookingStatusDistribution = Booking::whereIn('basecamp_id', $basecampIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        return response()->json([
            'message' => 'Data Chart Dashboard',
            'data' => [
                'income_by_month' => $incomeByMonth,
                'booking_status_distribution' => $bookingStatusDistribution
            ]
        ]);
    }
}