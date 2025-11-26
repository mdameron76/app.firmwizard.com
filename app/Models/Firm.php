<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Firm extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'website_url',
        'primary_email',
        'primary_phone',
        'timezone',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(FirmProfile::class);
    }

    public function onboardingSessions(): HasMany
    {
        return $this->hasMany(FirmOnboardingSession::class);
    }

    public function currentOnboardingSession(): HasOne
    {
        return $this->hasOne(FirmOnboardingSession::class)
            ->where('is_completed', false)
            ->latestOfMany();
    }

    public function aiFirmProfiles(): HasMany
    {
        return $this->hasMany(AiFirmProfile::class);
    }

    public function activeAiFirmProfile(): HasOne
    {
        return $this->hasOne(AiFirmProfile::class)
            ->where('is_active', true);
    }

    public function metricsDaily(): HasMany
    {
        return $this->hasMany(FirmMetricDaily::class);
    }

    public function metricsMonthly(): HasMany
    {
        return $this->hasMany(FirmMetricMonthly::class);
    }

    public function aiJobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }
}
