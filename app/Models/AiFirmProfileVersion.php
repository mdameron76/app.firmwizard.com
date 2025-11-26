<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiFirmProfileVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'firm_id',
        'ai_firm_profile_id',
        'profile_json',
    ];

    protected $casts = [
        'profile_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function aiFirmProfile(): BelongsTo
    {
        return $this->belongsTo(AiFirmProfile::class);
    }
}
