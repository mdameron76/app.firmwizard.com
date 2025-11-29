<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FwPixelApiKey extends Model
{
    protected $table = 'fw_pixel_api_keys';

    protected $fillable = [
        'firm_id',
        'api_key',
        'domain',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function firm(): BelongsTo
    {
        return $this->belongsTo(Firm::class);
    }

    /**
     * Generate a new pixel API key
     */
    public static function generateKey(): string
    {
        return 'FWPXL_' . Str::random(60);
    }

    /**
     * Create a new pixel API key for a firm
     */
    public static function createForFirm(int $firmId, ?string $domain = null): self
    {
        return self::create([
            'firm_id' => $firmId,
            'api_key' => self::generateKey(),
            'domain' => $domain,
            'is_active' => true,
        ]);
    }
}
