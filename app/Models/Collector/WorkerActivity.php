<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class WorkerActivity extends Model
{
    protected $connection = 'collector';
    protected $table = 'worker_activity';
    public $timestamps = false;

    protected $fillable = [
        'worker_type',
        'worker_action',
        'target_type',
        'target_id',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'items_processed',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'duration_seconds' => 'integer',
        'items_processed' => 'integer',
        'metadata' => 'array',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_type', 'name');
    }
}
