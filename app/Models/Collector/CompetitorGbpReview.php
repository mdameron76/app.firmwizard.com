<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CompetitorGbpReview extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitor_gbp_reviews';
    public $timestamps = false;

    protected $fillable = [
        'location_id', 'competitor_id', 'review_name', 'reviewer_name',
        'reviewer_profile_photo_url', 'star_rating', 'comment', 'review_reply',
        'review_reply_time', 'create_time', 'update_time', 'raw_response',
    ];

    protected $casts = [
        'raw_response' => 'array',
        'review_reply_time' => 'datetime',
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
