<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GunungGaleri extends Model
{
    //
    protected $fillable = [
        'foto',
        'caption',
        'gunung_id'
    ];

    public function gunung()
    {
        return $this->belongsTo(Gunung::class);
    }
}
