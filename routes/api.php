<?php

use App\Http\Controllers\Auth\AuthController;

use App\Http\Controllers\SuperAdmin\UserController;
use App\Http\Controllers\SuperAdmin\AdminRequestController;
use App\Http\Controllers\SuperAdmin\GunungController as SuperAdminGunungController;
use App\Http\Controllers\SuperAdmin\DashboardController;

use App\Http\Controllers\AdminGunung\GunungController as AdminGunungGunungController;

use App\Http\Controllers\User\GunungController as UserGunungController;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Protected route for admin role
Route::middleware(['auth:sanctum', 'role:admin_gunung,super_admin,admin_basecamp'])->get('/admin', function () {
    return response()->json([
        'message' => 'Admin Masuk'
    ]);
});

// Admin Gunung Routes 
Route::prefix('admin_gunung')
->middleware(['auth:sanctum', 'role:admin_gunung'])
->group(function () {
    Route::middleware(['auth:sanctum', 'role:admin_gunung'])->post(
    '/requests-admin-basecamp', [AdminRequestController::class, 'requestAdminBasecamp']);

    Route::get('/gunungs', [AdminGunungGunungController::class, 'index']);  
    Route::get('/gunungs/{id}', [AdminGunungGunungController::class, 'show']);
    Route::post('/gunungs', [AdminGunungGunungController::class, 'store']);
    Route::put('/gunungs/{id}', [AdminGunungGunungController::class, 'update']);
    Route::delete('/gunungs/{id}', [AdminGunungGunungController::class, 'destroy']);
    Route::post('/gunungs/{id}/galeri', [AdminGunungGunungController::class, 'tambahGaleri']);
}); 

// Super Admin Routes

Route::prefix('super_admin')
->middleware(['auth:sanctum', 'role:super_admin'])
->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('/requests', [AdminRequestController::class, 'index']);
    Route::post('/requests/{id}/approve', [AdminRequestController::class, 'approve']);
    Route::post('/requests/{id}/reject', [AdminRequestController::class, 'reject']);

    Route::get('/users', [UserController::class, 'index']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);
    Route::delete('/users/{id}/roles/{role}', [UserController::class, 'removeRole']); 

    Route::get('/gunungs', [SuperAdminGunungController::class, 'index']); 
});


// User Routes
Route::middleware(['auth:sanctum'])->post(
    '/requests-admin-gunung', [AdminRequestController::class, 'requestAdminGunung']);

Route::get('/gunungs', [UserGunungController::class, 'index']);
Route::get('/gunungs/{id}', [UserGunungController::class, 'show']);