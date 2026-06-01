<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Database\Factories\MeasurementFamilyFactory;

class MeasurementFamily extends Model implements HistoryAuditable
{
    use HasFactory, HistoryTrait;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'code',
        'name',
        'labels',
        'standard_unit',
        'units',
        'symbol',
    ];

    /**
     * Attribute type casting.
     */
    protected $casts = [
        'units'  => 'array',
        'labels' => 'array',
    ];

    protected $historyTags = ['Measurement Family'];

    protected $historyColumns = [
        'code',
        'name',
        'labels',
        'standard_unit',
        'units',
        'symbol',
    ];

    /**
     * Create new model factory instance.
     *
     * @return MeasurementFamilyFactory
     */
    protected static function newFactory()
    {
        return MeasurementFamilyFactory::new();
    }

    /**
     * Get units array attribute.
     *
     * @return array
     */
    public function getUnitsArrayAttribute()
    {
        return $this->units ?? [];
    }
}
