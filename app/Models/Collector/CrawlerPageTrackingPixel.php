<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPageTrackingPixel extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_page_tracking_pixels';
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'pixel_type',
        'pixel_id',
        'found_in',
        'raw_snippet',
    ];

    public function page()
    {
        return $this->belongsTo(CrawlerPage::class, 'page_id');
    }
}
