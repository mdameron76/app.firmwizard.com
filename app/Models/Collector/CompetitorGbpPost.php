<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CompetitorGbpPost extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitor_gbp_posts';
    public $timestamps = false;

    protected $fillable = [
        'location_id', 'competitor_id', 'post_name', 'post_type', 'summary',
        'call_to_action_type', 'call_to_action_url', 'media_format', 'media_url',
        'event_title', 'event_start_date', 'event_end_date', 'offer_coupon_code',
        'offer_redeem_url', 'offer_terms', 'language_code', 'state', 'create_time',
        'update_time', 'search_url', 'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'event_start_date' => 'datetime',
        'event_end_date' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    public function location()
    {
        return $this->belongsTo(CompetitorGbpLocation::class, 'location_id');
    }

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }
}
