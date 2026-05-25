<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class jalur extends Model
{
    protected $fillable = [
        'basecamp_id',
        'nama_jalur',
        'estimasi_waktu',
        'status',
        'deskripsi',
        'foto_utama',
    ];
    
    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }

    public function kuota()
    {
        return $this->hasOne(Jalur::class);
    }
}
