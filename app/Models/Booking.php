<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BookingMember;
use App\Models\DetailSewa;

class Booking extends Model
{
    use HasFactory;

    /**
     * Kode booking yang ditampilkan ke user/admin.
     * order_id dibuat saat booking, sedangkan data lama bisa belum punya
     * order_id sehingga dipakai fallback BOOK-{id}.
     */
    protected $appends = ['kode_booking'];

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

    /**
     * Kode booking yang dipakai di e-tiket/QR dan halaman validasi.
     */
    public function getKodeBookingAttribute(): string
    {
        return $this->order_id ?: ('BOOK-' . $this->id);
    }

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'bookings_id');
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

    // =========================
    // RELASI TRACKING GPS
    // =========================
    public function trackingSessions()
    {
        return $this->hasMany(TrackingSession::class);
    }

    public function activeTrackingSession()
    {
        return $this->hasOne(TrackingSession::class)->where('status', 'active')->latestOfMany();
    }

    public function getIsHikingAttribute(): bool
    {
        return $this->checkin_at !== null && $this->checkout_at === null;
    }
}
