<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingMember extends Model
{
    protected $fillable = [
        'booking_id',
        'nama',
        'alamat',
        'tanggal_lahir',
        'jenis_kelamin',
        'identitas',
        'no_hp',
        'foto_identitas',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}