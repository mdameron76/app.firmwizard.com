<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GbpLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'firm_id',
        'gbp_location_id',
        'place_id',
        'name',
        'formatted_address',
        'formatted_phone_number',
        'website',
        'opening_hours',
        'types',
        'rating',
        'user_ratings_total',
        'reviews',
        'photos',
        'raw_data',
        'last_synced_at',
        'status',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'types' => 'array',
        'reviews' => 'array',
        'photos' => 'array',
        'raw_data' => 'array',
        'last_synced_at' => 'datetime',
        'rating' => 'decimal:2',
        'user_ratings_total' => 'integer',
    ];

    /**
     * Relationship: GbpLocation belongs to a Firm
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Update last synced timestamp
     */
    public function updateSyncTime(): void
    {
        $this->update(['last_synced_at' => now()]);
    }
}
