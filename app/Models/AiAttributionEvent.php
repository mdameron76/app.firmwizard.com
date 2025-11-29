<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAttributionEvent extends Model
{
    protected $table = 'ai_attribution_events';

    protected $fillable = [
        'firm_id',
        'fwid',
        'gclid',
        'fbclid',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'url',
        'referrer',
        'user_agent',
        'ip_address',
        'crm_lead_id',
        'crm_case_id',
        'matched_with',
        'event_type',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
