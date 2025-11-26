<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CompetitorMetaAd extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitor_meta_ads';

    protected $fillable = [
        'competitor_id', 'ad_archive_id', 'page_id', 'page_name', 'ad_library_url',
        'ad_body_text', 'ad_title', 'cta_text', 'cta_type', 'link_url',
        'link_description', 'display_format', 'publisher_platforms', 'page_categories',
        'is_active', 'ad_start_date', 'ad_end_date', 'currency', 'spend_min',
        'spend_max', 'impressions_range', 'creative_cards', 'extra_images',
        'extra_videos', 'extra_texts', 'extra_links', 'page_profile_picture_url',
        'page_profile_uri', 'page_like_count', 'page_is_deleted', 'raw_snapshot',
        'collection_status', 'collection_notes', 'first_seen_at', 'last_seen_active',
        'last_scraped_at', 'sync_status', 'synced_at',
    ];

    protected $casts = [
        'publisher_platforms' => 'array',
        'page_categories' => 'array',
        'creative_cards' => 'array',
        'extra_images' => 'array',
        'extra_videos' => 'array',
        'extra_texts' => 'array',
        'extra_links' => 'array',
        'raw_snapshot' => 'array',
        'is_active' => 'boolean',
        'page_is_deleted' => 'boolean',
        'ad_start_date' => 'datetime',
        'ad_end_date' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_active' => 'datetime',
        'last_scraped_at' => 'datetime',
        'synced_at' => 'datetime',
        'spend_min' => 'decimal:2',
        'spend_max' => 'decimal:2',
    ];

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }
}
