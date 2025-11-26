<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPage extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_pages';

    protected $fillable = [
        'competitor_id',
        'url',
        'url_hash',
        'title',
        'content',
        'content_hash',
        'meta_description',
        'meta_keywords',
        'word_count',
        'page_type',
        'status_code',
        'crawled_at',
        'synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'word_count' => 'integer',
        'status_code' => 'integer',
        'crawled_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }

    public function headings()
    {
        return $this->hasMany(CrawlerPageHeading::class, 'page_id');
    }

    public function keywords()
    {
        return $this->hasMany(CrawlerPageKeyword::class, 'page_id');
    }

    public function links()
    {
        return $this->hasMany(CrawlerPageLink::class, 'page_id');
    }

    public function schemas()
    {
        return $this->hasMany(CrawlerPageSchema::class, 'page_id');
    }

    public function trackingPixels()
    {
        return $this->hasMany(CrawlerPageTrackingPixel::class, 'page_id');
    }
}
