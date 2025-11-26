<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    protected $connection = 'collector';
    protected $table = 'workers';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'script_path',
        'enabled',
        'icon',
        'color',
        'accepts_collection_id',
        'accepts_competitor_id',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'accepts_collection_id' => 'boolean',
        'accepts_competitor_id' => 'boolean',
    ];

    public function activities()
    {
        return $this->hasMany(WorkerActivity::class, 'worker_type', 'name');
    }
}
