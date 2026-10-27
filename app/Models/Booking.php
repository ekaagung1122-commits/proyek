<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BookingMember;
use App\Models\DetailSewa;

class Booking extends Model
{
    use HasFactory;

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
        'total_sewa_alat',
        'promo_id',
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

    public function members()
    {
        return $this->hasMany(BookingMember::class);
    }

    public function promo()
    {
        return $this->belongsTo(Promo::class, 'promo_id');
    }

    public function detailSewa()
    {
        return $this->hasMany(DetailSewa::class, 'booking_id', 'id');
    }
}
