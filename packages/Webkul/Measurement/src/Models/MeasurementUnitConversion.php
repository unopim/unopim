<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Contracts\MeasurementUnitConversion as MeasurementUnitConversionContract;

#[Fillable([
    'measurement_unit_id',
    'value',
    'operator',
    'position',
])]
#[Table(name: 'measurement_unit_conversions')]
#[WithoutTimestamps]
class MeasurementUnitConversion extends Model implements HistoryAuditable, MeasurementUnitConversionContract
{
    use HistoryTrait;

    /**
     * Group conversion history under the parent measurement family entity.
     */
    protected $historyTags = ['Measurement'];

    /**
     * Only audit the meaningful conversion fields (not the ordering position).
     */
    protected $auditInclude = [
        'operator',
        'value',
    ];

    /**
     * Get the measurement unit that owns the conversion step.
     *
     * @return BelongsTo<MeasurementUnit, $this>
     */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id');
    }

    /**
     * Group history versions under the related measurement family id
     * (resolved through the owning unit).
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) ($this->unit?->measurement_family_id ?? 0);
    }
}
