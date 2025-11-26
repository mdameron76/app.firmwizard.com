<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class Competitor extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitors';

    protected $fillable = [
        'firm_id',
        'firm_location_id',
        'collection_id',
        'name',
        'website',
        'facebook_page_url',
        'address',
        'phone',
        'place_id',
        'blocked_at',
        'block_reason',
        'facebook_last_scraped',
        'facebook_ads_count',
        'facebook_scrape_status',
        'active',
        'gbp_location_count',
        'gbp_last_scraped',
        'gbp_scrape_status',
        'gbp_account_name',
    ];

    protected $casts = [
        'active' => 'boolean',
        'facebook_ads_count' => 'integer',
        'gbp_location_count' => 'integer',
        'blocked_at' => 'datetime',
        'facebook_last_scraped' => 'datetime',
        'gbp_last_scraped' => 'datetime',
    ];

    public function pages()
    {
        return $this->hasMany(CrawlerPage::class, 'competitor_id');
    }

    public function practiceAreas()
    {
        return $this->hasMany(CompetitorPracticeArea::class, 'competitor_id');
    }

    public function gbpLocations()
    {
        return $this->hasMany(CompetitorGbpLocation::class, 'competitor_id');
    }

    public function metaAds()
    {
        return $this->hasMany(CompetitorMetaAd::class, 'competitor_id');
    }

    public function collections()
    {
        return $this->belongsToMany(
            Collection::class,
            'competitor_collections',
            'competitor_id',
            'collection_id'
        )->withTimestamps();
    }
}
