<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ForgotPasswordController;

use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\AdminRequestController;
use App\Http\Controllers\SuperAdmin\GunungController as SuperAdminGunungController;
use App\Http\Controllers\SuperAdmin\DashboardController;
use App\Http\Controllers\SuperAdmin\ActivityLogController;

use App\Http\Controllers\AdminGunung\GunungController as AdminGunungGunungController;
use App\Http\Controllers\AdminGunung\AdminRequestController as AdminGunungAdminRequestController;
use App\Http\Controllers\AdminGunung\ReportController;
use App\Http\Controllers\AdminGunung\BasecampController;
use App\Http\Controllers\AdminGunung\DashboardController as AdminGunungDashboardController;

use App\Http\Controllers\AdminBasecamp\BookingController as AdminBasecampBookingController;
use App\Http\Controllers\AdminBasecamp\KuotaController;
use App\Http\Controllers\AdminBasecamp\DashboardController as AdminBasecampDashboardController;
use App\Http\Controllers\AdminBasecamp\JalurController;
use App\Http\Controllers\AdminBasecamp\ReportController as AdminBasecampReportController;

use App\Http\Controllers\User\GunungController as UserGunungController;
use App\Http\Controllers\User\BookingController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReviewController;
use App\Http\Controllers\User\AdminRequestController as UserAdminRequestController;

use App\Http\Controllers\NotificationController;

use App\Http\Controllers\PaymentCallbackController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// belum ditest postman
Route::post('/password/forgot', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('/password/reset', [ForgotPasswordController::class, 'resetPassword']);

// Protected route for admin role
Route::middleware(['auth:sanctum', 'role:admin_gunung,super_admin,admin_basecamp'])->get('/admin', function () {
    return response()->json([
        'message' => 'Admin Masuk'
    ]);
});

// Admin Basecamp Routes
// belum ditest postman
Route::prefix('admin-basecamp')
->middleware(['auth:sanctum', 'role:admin_basecamp'])
->group(function () {
    Route::get('/bookings', [AdminBasecampBookingController::class, 'index']);
    Route::get('/bookings/{id}', [AdminBasecampBookingController::class, 'show']);
    Route::patch('/bookings/{id}/checkin', [AdminBasecampBookingController::class, 'checkin']);
    Route::patch('/bookings/{id}/checkout', [AdminBasecampBookingController::class, 'checkout']);

    Route::get('/basecamps/{basecampId}/kuotas', [KuotaController::class, 'index']);
    Route::get('/basecamps/{basecampId}/kuotas/{id}', [KuotaController::class, 'show']);
    Route::post('/basecamps/{basecampId}/kuotas', [ KuotaController::class, 'store']);
    Route::put('/basecamps/{basecampId}/kuotas/{id}', [KuotaController::class, 'update']);
    Route::delete('/basecamps/{basecampId}/kuotas/{id}', [KuotaController::class, 'destroy']);
    
    Route::get('/basecamps/{basecampId}/jalurs', [JalurController::class, 'index']);
    Route::get('/basecamps/{basecampId}/jalurs/{id}', [JalurController::class, 'show']);
    Route::post('/basecamps/{basecampId}/jalurs', [JalurController::class, 'store']);
    Route::put('/basecamps/{basecampId}/jalurs/{id}', [JalurController::class, 'update']);
    Route::delete('/basecamps/{basecampId}/jalurs/{id}', [JalurController::class, 'destroy']);

    Route::get('/dashboard', [AdminBasecampDashboardController::class, 'index']);
    Route::get('/dashboard/charts', [AdminBasecampDashboardController::class, 'chart']);
    
    Route::get('/reports', [AdminBasecampReportController::class, 'index']);
    Route::get('/reports/pdf', [AdminBasecampReportController::class, 'downloadPdf']);
    });

// Admin Gunung Routes 
Route::prefix('admin-gunung')
->middleware(['auth:sanctum', 'role:admin_gunung'])
->group(function () {
    Route::post('/requests', [AdminGunungAdminRequestController::class, 'requestAdminBasecamp']);
    Route::get('/requests', [AdminGunungAdminRequestController::class, 'index']);

    Route::get('/gunungs', [AdminGunungGunungController::class, 'index']);  
    Route::get('/gunungs/{id}', [AdminGunungGunungController::class, 'show']);
    Route::post('/gunungs', [AdminGunungGunungController::class, 'store']);
    Route::put('/gunungs/{id}', [AdminGunungGunungController::class, 'update']);
    Route::delete('/gunungs/{id}', [AdminGunungGunungController::class, 'destroy']);
    Route::post('/gunungs/{id}/galeri', [AdminGunungGunungController::class, 'tambahGaleri']);

    // belum ditest postman
    Route::get('/basecamps', [BasecampController::class, 'index']);
    Route::get('/basecamps/{id}', [BasecampController::class, 'show']);
    Route::post('/basecamps', [BasecampController::class, 'store']);
    Route::put('/basecamps/{id}', [BasecampController::class, 'update']);
    Route::delete('/basecamps/{id}', [BasecampController::class, 'destroy']);
    Route::patch('/basecamps/{id}/assign-admin', [BasecampController::class, 'assignAdmin']);

    // belum ditest postman
    Route::get('/reports', [ReportController::class, 'index']);
    Route::get('/reports/pdf', [ReportController::class, 'downloadPdf']);

    // belum ditest postman
    Route::get('/dashboard', [AdminGunungDashboardController::class, 'index']);
    Route::get('/dashboard/charts', [AdminGunungDashboardController::class, 'chart']);
}); 

// Super Admin Routes
Route::prefix('super-admin')
->middleware(['auth:sanctum', 'role:super_admin'])
->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/charts', [DashboardController::class, 'chart']); //blm di test

    Route::get('/requests', [AdminRequestController::class, 'index']);
    Route::post('/requests/{id}/approve', [AdminRequestController::class, 'approve']);
    Route::post('/requests/{id}/reject', [AdminRequestController::class, 'reject']);

    Route::get('/users', [UserController::class, 'index']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/users/{id}/roles/{role}', [UserController::class, 'removeRole']); 

    Route::get('/gunungs', [SuperAdminGunungController::class, 'index']); 

    // belum ditest postman
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});


// User Routes
Route::prefix('user')
->group(function () {
    Route::get('/gunungs', [UserGunungController::class, 'index']);
    Route::get('/gunungs/{id}', [UserGunungController::class, 'show']);

    Route::get('/gunungs/{gunungId}/reviews', [ReviewController::class, 'gunungReviews']);
});

Route::prefix('user')
->middleware(['auth:sanctum'])
->group(function () {
    Route::post('/requests', [UserAdminRequestController::class, 'requestAdminGunung']);

// belum ditest postman
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::patch('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/history', [BookingController::class, 'history']);
    Route::get('/bookings/{id}/pdf', [BookingController::class, 'downloadPdf']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::patch('/profile/foto', [ProfileController::class, 'uploadFoto']);
    Route::patch('/profile/change-password', [ProfileController::class, 'changePassword']);

    Route::post('/reviews', [ReviewController::class, 'store']);
});

// Notifications route  
// belum ditest postman
Route::prefix('notifications')
->middleware(['auth:sanctum'])
->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::post('/{id}/read', [NotificationController::class, 'read']);
    Route::post('/{id}/unread', [NotificationController::class, 'unread']);
    Route::post('/read-all', [NotificationController::class, 'readAll']);
    Route::delete('/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/clear-all', [NotificationController::class, 'clearAll']);
});

// Midtrans route
// belum ditest postman
Route::post('/payment/callback', [PaymentCallbackController::class, 'handle']);