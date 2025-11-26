<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class Collection extends Model
{
    protected $connection = 'collector';
    protected $table = 'collections';

    protected $fillable = [
        'firm_id',
        'firm_location_id',
        'name',
        'query',
        'worker_id',
        'enabled',
        'schedule_cron',
        'last_run_at',
        'next_run_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function runs()
    {
        return $this->hasMany(CollectionRun::class, 'collection_id');
    }

    public function competitors()
    {
        return $this->belongsToMany(
            Competitor::class,
            'competitor_collections',
            'collection_id',
            'competitor_id'
        )->withTimestamps();
    }
}
