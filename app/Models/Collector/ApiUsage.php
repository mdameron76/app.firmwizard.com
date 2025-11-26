<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class ApiUsage extends Model
{
    protected $connection = 'collector';
    protected $table = 'api_usage';
    public $timestamps = false;

    protected $fillable = [
        'vendor',
        'api_name',
        'collection_run_id',
        'units',
        'cost_cents',
        'called_at',
    ];

    protected $casts = [
        'units' => 'integer',
        'cost_cents' => 'integer',
        'called_at' => 'datetime',
    ];

    public function collectionRun()
    {
        return $this->belongsTo(CollectionRun::class, 'collection_run_id');
    }
}
