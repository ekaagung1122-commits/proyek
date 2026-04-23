<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_by',
        'request_type',
        'status',
        'notes'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(AdminRequestDocument::class);
    }
}
