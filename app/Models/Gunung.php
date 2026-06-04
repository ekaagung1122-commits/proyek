<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Gunung extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nama',
        'lokasi',
        'foto_utama',
        'deskripsi',
        'status',
        'ketinggian',
        'created_by'
    ];

    public function galeris(){
        return $this->hasMany(GunungGaleri::class);
    }

     public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function basecamps()
    {
        return $this->hasMany(Basecamp::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
