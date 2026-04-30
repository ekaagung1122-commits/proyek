<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
