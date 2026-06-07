<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AdminRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'request_by',
        'request_type',
        'basecamp_id',
        'status',
        'notes',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function documents()
    {
        return $this->hasMany(AdminRequestDocument::class);
    }
}
