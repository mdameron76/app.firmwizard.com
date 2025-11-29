<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FwPixelEventRaw extends Model
{
    const UPDATED_AT = null;

    protected $table = 'fw_pixel_events_raw';

    protected $fillable = [
        'firm_id',
        'fwid',
        'payload_json',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
