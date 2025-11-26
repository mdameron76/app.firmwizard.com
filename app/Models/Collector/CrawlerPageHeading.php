<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPageHeading extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_page_headings';
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'level',
        'position',
        'anchor_id',
        'text',
    ];

    protected $casts = [
        'level' => 'integer',
        'position' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(CrawlerPage::class, 'page_id');
    }
}
