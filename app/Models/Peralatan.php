<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Peralatan extends Model
{
    //
    use HasFactory;

    protected $guarded = ['id'];

    public function basecamp(){
        return $this->belongsTo(Basecamp::class, 'basecamp_id');
    }

    public function kategori(){
        return $this->belongsTo(KategoriPeralatan::class, 'kategori_id');
    }

    public function detailSewas(){
        return $this->hasMany(DetailSewa::class, 'peralatan_id');
    }

}
