<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiFirmProfile extends Model
{
    protected $fillable = [
        'firm_id',
        'source',
        'profile_json',
        'version',
        'is_active',
    ];

    protected $casts = [
        'profile_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AiFirmProfileVersion::class);
    }
}
