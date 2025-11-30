<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GbpLocationHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'gbp_location_id',
        'firm_id',
        'gbp_location_string_id',
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
        'synced_at',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'types' => 'array',
        'reviews' => 'array',
        'photos' => 'array',
        'raw_data' => 'array',
        'synced_at' => 'datetime',
        'rating' => 'decimal:2',
        'user_ratings_total' => 'integer',
    ];

    /**
     * Relationship: History entry belongs to a GbpLocation
     */
    public function gbpLocation(): BelongsTo
    {
        return $this->belongsTo(GbpLocation::class);
    }

    /**
     * Relationship: History entry belongs to a Firm
     */
    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
