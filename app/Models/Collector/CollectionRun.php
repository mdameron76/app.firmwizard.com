<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CollectionRun extends Model
{
    protected $connection = 'collector';
    protected $table = 'collection_runs';
    public $timestamps = false;

    protected $fillable = [
        'collection_id',
        'started_at',
        'finished_at',
        'status',
        'error_message',
        'items_processed',
        'api_calls',
        'api_cost_estimate_cents',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'items_processed' => 'integer',
        'api_calls' => 'integer',
        'api_cost_estimate_cents' => 'integer',
    ];

    public function collection()
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }
}
