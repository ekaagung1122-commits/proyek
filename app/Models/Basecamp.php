<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Basecamp extends Model
{
    public function gunung()
    {
        return $this->belongsTo(Gunung::class);
    }
}
