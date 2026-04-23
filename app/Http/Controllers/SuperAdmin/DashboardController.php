<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Role;
use App\Models\Gunung;
use App\Models\AdminRequest;
use App\Models\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $adminGunung = Role::where('name', 'admin_gunung')->first();
        $adminBasecamp = Role::where('name', 'admin_basecamp')->first();
    
        return response()->json([
            'total_users' => User::count(),
            'total_admin_gunung' => $adminGunung ? $adminGunung->users()->count() : 0,
            'total_admin_basecamp' => $adminBasecamp ? $adminBasecamp->users()->count() : 0,
            'total_gunung' => Gunung::count(),
            'pending_requests' => AdminRequest::where('status', 'pending')->count(),
            'approved_requests' => AdminRequest::where('status', 'approved')->count(),
            'rejected_requests' => AdminRequest::where('status', 'rejected')->count()
        ]);
    }
}
