<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FirmMetricMonthly extends Model
{
    protected $table = 'firm_metrics_monthly';
    
    public $timestamps = false;

    protected $fillable = [
        'firm_id',
        'month',
        'metric_type',
        'metric_value',
        'meta_json',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'meta_json' => 'array',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }
}
