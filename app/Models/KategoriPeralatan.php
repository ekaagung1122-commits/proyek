<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Fluent\Concerns\Has;

class KategoriPeralatan extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    public function peralatans(){
        return $this->hasMany(Peralatan::class, 'kategori_id');
    }
}
