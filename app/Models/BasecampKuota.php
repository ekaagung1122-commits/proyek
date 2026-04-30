<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasecampKuota extends Model
{
    protected $fillable = [
        'basecamp_id',
        'tanggal',
        'kuota'
    ];

    public function basecamp()
    {
        return $this->belongsTo(Basecamp::class);
    }
}
