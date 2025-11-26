<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmProfile extends Model
{
    protected $fillable = [
        'firm_id',
        'main_office_city',
        'main_office_state',
        'main_office_zip',
        'practice_areas_json',
        'brand_voice_json',
        'target_demographics_json',
        'service_areas_json',
        'marketing_goals_json',
        'onboarding_completed_at',
    ];

    protected $casts = [
        'practice_areas_json' => 'array',
        'brand_voice_json' => 'array',
        'target_demographics_json' => 'array',
        'service_areas_json' => 'array',
        'marketing_goals_json' => 'array',
        'onboarding_completed_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
