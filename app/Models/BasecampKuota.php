<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasecampKuota extends Model
{
    protected $fillable = [
        'basecamp_id',
        'tanggal',
        'kuota',
        'kuota_terpakai'
    ];

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }

    public function jalur()
    {
        return $this->belongsTo(Jalur::class);
    }
}
