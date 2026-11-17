<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'user_id',
        'basecamp_id',
        'status',
        'started_at',
        'ended_at',
        'ended_reason',
        'start_distance_m',
        'last_latitude',
        'last_longitude',
        'last_accuracy',
        'last_altitude',
        'last_speed',
        'last_battery',
        'last_recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'last_recorded_at' => 'datetime',
            'last_latitude' => 'float',
            'last_longitude' => 'float',
            'last_accuracy' => 'float',
            'last_altitude' => 'float',
            'last_speed' => 'float',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }

    public function points()
    {
        return $this->hasMany(TrackingPoint::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }
}
