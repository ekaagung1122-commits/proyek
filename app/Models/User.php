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

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function adminRequests()
    {
        return $this->hasMany(AdminRequest::class);
    }

    public function basecamps()
    {
        return $this->hasMany(Basecamp::class, 'admin_basecamp_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
