<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;

class AttributeMeasurement extends Model
{
    protected $table = 'attribute_measurement';

    protected $fillable = [
        'attribute_id',
        'family_code',
        'unit_code',
    ];

    public function attribute()
    {
        return $this->belongsTo(\Webkul\Attribute\Models\Attribute::class, 'attribute_id');
    }

    public function family()
    {
        return $this->belongsTo(
            MeasurementFamily::class,
            'family_code',
            'code'
        );
    }

    public function getUnitAttribute()
    {
        if (! $this->family) {
            return null;
        }

        return collect($this->family->units_array)
            ->firstWhere('id', $this->unit_code);
    }
}
