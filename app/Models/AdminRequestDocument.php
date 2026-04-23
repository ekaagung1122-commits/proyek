<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminRequestDocument extends Model
{
    protected $fillable = [
        'admin_request_id',
        'document_name',
        'document_path'
    ];

    public function adminRequest()
    {
        return $this->belongsTo(AdminRequest::class, 'admin_request_id');
    }
}
