<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class CompetitorPracticeArea extends Model
{
    protected $connection = 'collector';
    protected $table = 'competitor_practice_areas';
    public $timestamps = false;

    protected $fillable = [
        'competitor_id',
        'practice_area',
    ];

    public function competitor()
    {
        return $this->belongsTo(Competitor::class, 'competitor_id');
    }
}
