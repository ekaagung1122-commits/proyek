<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'bookings_id',
        'gunung_id',
        'basecamp_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'bookings_id');
    }

    public function gunung()
    {
        return $this->belongsTo(Gunung::class);
    }

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }
}
