<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'user_id',
        'basecamp_id',
        'tanggal_naik',
        'jumlah_pendaki',
        'harga_per_orang',
        'total_price',
        'status',
        'order_id',
        'snap_token',
        'checkin_at',
        'checkin_by',
        'checkout_at',
        'checkout_by'
    ];

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