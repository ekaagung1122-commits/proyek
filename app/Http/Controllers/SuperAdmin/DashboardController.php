<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Role;
use App\Models\Gunung;
use App\Models\AdminRequest;
use App\Models\User;
use App\Models\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $adminGunung = Role::where('name', 'admin_gunung')->first();
        $adminBasecamp = Role::where('name', 'admin_basecamp')->first();
    
        return response()->json([
            'message' => 'Dashboard Super Admin',
            'data' => [
                'total_users' => User::count(),
                'total_admin_gunung' => $adminGunung ? $adminGunung->users()->count() : 0,
                'total_admin_basecamp' => $adminBasecamp ? $adminBasecamp->users()->count() : 0,
                'total_gunung' => Gunung::count(),
                'total_income' => Booking::where('status', ['confirmed', 'completed'])
                    ->sum('total_price'),
                'pending_requests' => AdminRequest::where('status', 'pending')->count(),
                'approved_requests' => AdminRequest::where('status', 'approved')->count(),
                'rejected_requests' => AdminRequest::where('status', 'rejected')->count()
            ]
        ]);
    }

    public function chart()
    {
        $role_distribution = Role::withCount('users')
        ->get() 
        ->pluck('users_count', 'name'); 

        $request_chart = AdminRequest::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $user_growth = User::selectRaw('MONTH(created_at) as month, COUNT(*) as count')
            ->groupBy('month')
            ->pluck('count', 'month');

        return response()->json([
            'message' => 'Data Chart Dashboard',
            'data' => [
                'role_distribution' => $role_distribution,
                'request_chart' => $request_chart,
                'user_growth' => $user_growth
            ]
        ]);
    }
}
