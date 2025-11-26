<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPageLink extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_page_links';
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'href',
        'anchor_text',
        'link_type',
        'rel',
        'target',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(CrawlerPage::class, 'page_id');
    }
}
