<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BasecampKuota extends Model
{
    use HasFactory;

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
}
