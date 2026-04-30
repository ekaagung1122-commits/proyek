<?php

namespace App\Http\Controllers\AdminGunung;

use App\Models\Gunung;
use App\Models\Basecamp;
use App\Models\Booking;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $gunungIds = Gunung::where('created_by', auth()->id())->pluck('id');
        $basecampIds = Basecamp::whereIn('gunung_id', $gunungIds)->pluck('id');
        $bookingQuery = Booking::whereIn('basecamp_id', $basecampIds);

        return response()->json([
            'message' => 'Dashboard Admin Gunung',
            'data' => [
                'total_gunung' => $gunungIds->count(),
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
                'admin_basecamp_aktif' => User::whereHas('roles', function($query) {
                    $query->where('name', 'admin_basecamp');
                })->whereHas('basecamps', function($query) use ($gunungIds) {
                    $query->whereIn('gunung_id', $gunungIds);
                })->count()
            ]
        ]);
    }

    public function chart()
    {
        $gunungIds = Gunung::where('created_by', auth()->id())->pluck('id');
        $basecampIds = Basecamp::whereIn('gunung_id', $gunungIds)->pluck('id');

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
