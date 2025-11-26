<?php

namespace App\Models\Collector;

use Illuminate\Database\Eloquent\Model;

class FirmLocation extends Model
{
    protected $connection = 'collector';
    protected $table = 'firm_locations';

    protected $fillable = [
        'firm_id',
        'external_location_id',
        'name',
        'address_line1',
        'city',
        'state',
        'zipcode',
        'latitude',
        'longitude',
        'radius_meters',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'radius_meters' => 'integer',
    ];

    public function collections()
    {
        return $this->hasMany(Collection::class, 'firm_location_id');
    }

    public function competitors()
    {
        return $this->hasMany(Competitor::class, 'firm_location_id');
    }
}
