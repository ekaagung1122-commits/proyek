<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Jalur extends Model
{
    use HasFactory;

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
