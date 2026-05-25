<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRequest extends Model
{
    protected $fillable = [
        'user_id',
        'request_by',
        'request_type',
        'basecamp_id',
        'status',
        'notes',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(AdminRequestDocument::class);
    }
}
