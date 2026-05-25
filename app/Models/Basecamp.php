<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basecamp extends Model
{
    protected $fillable = [
        'nama',
        'gunung_id',
        'lokasi',
        'harga_tiket',
        'admin_basecamp_id',
        'kuota',
        'foto_utama',
    ];

    // =========================
    // RELASI GUNUNG
    // =========================
    public function gunung()
    {
        return $this->belongsTo(Gunung::class);
    }

    // =========================
    // RELASI KUOTA
    // =========================
    public function kuotas()
    {
        return $this->hasMany(BasecampKuota::class);
    }

    // =========================
    // RELASI JALUR
    // =========================
    public function jalurs()
    {
        return $this->hasMany(Jalur::class);
    }

    // =========================
    // RELASI REVIEW
    // =========================
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // =========================
    // RELASI ADMIN BASECAMP
    // =========================
    public function adminBasecamp()
    {
        return $this->belongsTo(
            User::class,
            'admin_basecamp_id'
        );
    }
}