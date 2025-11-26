<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPageKeyword extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_page_keywords';
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'ngram_type',
        'keyword',
        'frequency',
        'density',
        'first_position',
        'first_position_percent',
        'in_title',
        'in_meta_description',
        'in_h1',
        'in_h2',
        'in_url_slug',
    ];

    protected $casts = [
        'ngram_type' => 'integer',
        'frequency' => 'integer',
        'density' => 'float',
        'first_position' => 'integer',
        'first_position_percent' => 'float',
        'in_title' => 'boolean',
        'in_meta_description' => 'boolean',
        'in_h1' => 'boolean',
        'in_h2' => 'boolean',
        'in_url_slug' => 'boolean',
    ];

    public function page()
    {
        return $this->belongsTo(CrawlerPage::class, 'page_id');
    }
}
