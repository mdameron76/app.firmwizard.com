<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    use HasFactory;

    protected $fillable = [
        'firm_id',
        'platform',
        'name',
        'auth_type',
        'credentials',
        'config',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'config' => 'array',
        'last_synced_at' => 'datetime',
    ];

    protected $hidden = [
        'credentials',
    ];

    /**
     * Relationship: Integration belongs to a Firm
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Get decrypted credentials
     */
    public function getCredentialsAttribute($value): array
    {
        if (!$value) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($value), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set encrypted credentials
     */
    public function setCredentialsAttribute($value): void
    {
        $this->attributes['credentials'] = Crypt::encryptString(json_encode($value));
    }

    /**
     * Scope: Get active integrations
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Get integrations by platform
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Check if integration is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mark integration as active
     */
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Mark integration as inactive
     */
    public function deactivate(): void
    {
        $this->update(['status' => 'inactive']);
    }

    /**
     * Mark integration as errored
     */
    public function markAsError(): void
    {
        $this->update(['status' => 'error']);
    }

    /**
     * Update last synced timestamp
     */
    public function updateSyncTime(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
}
