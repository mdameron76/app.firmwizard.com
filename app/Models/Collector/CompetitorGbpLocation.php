<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CompetitorGbpLocation extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitor_gbp_locations';

    protected $fillable = [
        'competitor_id', 'location_name', 'place_id', 'store_code', 'business_name',
        'language_code', 'title', 'description', 'address_line1', 'address_line2',
        'locality', 'administrative_area', 'postal_code', 'country', 'latitude',
        'longitude', 'primary_category', 'additional_categories', 'phone_number',
        'website_url', 'service_areas', 'attributes', 'regular_hours', 'special_hours',
        'is_verified', 'is_published', 'is_suspended', 'is_duplicate', 'profile_photo_url',
        'cover_photo_url', 'logo_url', 'photos_count', 'rating', 'review_count',
        'total_reviews', 'opening_date', 'price_level', 'completeness_score',
        'completeness_details', 'labels', 'raw_response', 'sync_status', 'synced_at',
        'sync_error', 'first_seen_at', 'last_scraped_at',
    ];

    protected $casts = [
        'additional_categories' => 'array',
        'service_areas' => 'array',
        'attributes' => 'array',
        'regular_hours' => 'array',
        'special_hours' => 'array',
        'completeness_details' => 'array',
        'labels' => 'array',
        'raw_response' => 'array',
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'is_suspended' => 'boolean',
        'is_duplicate' => 'boolean',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:1',
        'synced_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_scraped_at' => 'datetime',
        'opening_date' => 'date',
    ];

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }

    public function reviews()
    {
        return $this->hasMany(CompetitorGbpReview::class, 'location_id');
    }

    public function posts()
    {
        return $this->hasMany(CompetitorGbpPost::class, 'location_id');
    }
}
