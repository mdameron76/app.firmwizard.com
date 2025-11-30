<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GbpInsight extends Model
{
    protected $table = 'gbp_insights';

    protected $fillable = [
        'firm_id',
        'location_id',
        'location_name',
        'date',
        'views_search',
        'views_maps',
        'actions_website',
        'actions_phone',
        'actions_driving_directions',
        'photos_count_merchant',
        'photos_count_customers',
        'photos_views_merchant',
        'photos_views_customers',
        'search_queries',
        'raw_data',
    ];

    protected $casts = [
        'date' => 'date',
        'search_queries' => 'array',
        'raw_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Get total views (search + maps)
     */
    public function getTotalViewsAttribute(): int
    {
        return $this->views_search + $this->views_maps;
    }

    /**
     * Get total actions
     */
    public function getTotalActionsAttribute(): int
    {
        return $this->actions_website + $this->actions_phone + $this->actions_driving_directions;
    }
}
