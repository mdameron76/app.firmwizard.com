<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CrawlerPageSchema extends Model
{
    protected $connection = 'collector';
    protected $table = 'crawler_page_schemas';
    public $timestamps = false;

    protected $fillable = [
        'page_id',
        'schema_type',
        'source',
        'schema_id',
        'name',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(CrawlerPage::class, 'page_id');
    }
}
