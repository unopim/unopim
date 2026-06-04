<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Database\Factories\MeasurementFamilyFactory;
use Webkul\Measurement\Presenters\LabelsPresenter;
use Webkul\Measurement\Presenters\UnitsPresenter;

class MeasurementFamily extends Model implements HistoryAuditable, PresentableHistoryInterface
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

    /**
     * Define custom presenters used when displaying history for JSON columns,
     * so that "labels" and "units" render as readable rows instead of raw arrays.
     */
    public static function getPresenters(): array
    {
        return [
            'labels' => LabelsPresenter::class,
            'units'  => UnitsPresenter::class,
        ];
    }
}
