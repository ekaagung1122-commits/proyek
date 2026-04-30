<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class jalur extends Model
{
    protected $fillable = [
        'basecamp_id',
        'nama_jalur',
        'estimaasi_waktu',
        'status',
        'deskripsi',
    ];
    
    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }
}
