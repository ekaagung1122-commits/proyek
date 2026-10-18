<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSewa extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    public function booking(){
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function peralatan(){
        return $this->belongsTo(Peralatan::class, 'peralatan_id');
    }
}
