<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'alamat',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // =========================
    // RELASI BOOKINGS
    // =========================
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // =========================
    // RELASI ROLES
    // =========================
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    // =========================
    // RELASI ADMIN REQUEST
    // =========================
    public function adminRequests()
    {
        return $this->hasMany(AdminRequest::class);
    }

    // =========================
    // RELASI BASECAMP
    // =========================
    public function basecamp()
    {
        return $this->hasOne(
            Basecamp::class,
            'admin_basecamp_id'
        );
    }

    // =========================
    // RELASI REVIEW
    // =========================
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}