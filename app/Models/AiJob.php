<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiJob extends Model
{
    protected $fillable = [
        'firm_id',
        'job_type',
        'input_json',
        'output_json',
        'status',
        'started_at',
        'finished_at',
        'error_message',
    ];

    protected $casts = [
        'input_json' => 'array',
        'output_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
