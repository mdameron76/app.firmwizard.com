<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmOnboardingSession extends Model
{
    protected $fillable = [
        'firm_id',
        'user_id',
        'current_step',
        'completed_steps_json',
        'wizard_data_json',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'completed_steps_json' => 'array',
        'wizard_data_json' => 'array',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
