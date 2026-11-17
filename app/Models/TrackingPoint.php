<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingPoint extends Model
{
    use HasFactory;

    /**
     * Tabel hanya memiliki kolom created_at (tanpa updated_at).
     */
    public const UPDATED_AT = null;

    protected $fillable = [
        'tracking_session_id',
        'client_uuid',
        'latitude',
        'longitude',
        'accuracy',
        'altitude',
        'speed',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'accuracy' => 'float',
            'altitude' => 'float',
            'speed' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function session()
    {
        return $this->belongsTo(TrackingSession::class, 'tracking_session_id');
    }
}
