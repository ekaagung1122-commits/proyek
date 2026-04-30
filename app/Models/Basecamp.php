<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basecamp extends Model
{
    public function gunung()
    {
        return $this->belongsTo(Gunung::class);
    }

    public function kuotas()
    {
        return $this->hasMany(BasecampKuota::class);
    }

    public function jalurs()
    {
        return $this->hasMany(jalur::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
