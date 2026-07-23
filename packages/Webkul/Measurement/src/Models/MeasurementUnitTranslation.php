<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Contracts\MeasurementUnitTranslation as MeasurementUnitTranslationContract;

#[Fillable([
    'measurement_unit_id',
    'locale',
    'label',
])]
#[Table(name: 'measurement_unit_translations')]
#[WithoutTimestamps]
class MeasurementUnitTranslation extends Model implements HistoryAuditable, MeasurementUnitTranslationContract
{
    use HistoryTrait;

    /**
     * Group unit-label history under the parent measurement family entity.
     */
    protected $historyTags = ['Measurement'];

    /**
     * Only audit the translated label.
     */
    protected $auditInclude = ['label'];

    /**
     * Render the unit-label change as a per-locale history row.
     */
    protected $historyTranslatableFields = [
        'label' => 'Unit Label',
    ];

    /**
     * Get the measurement unit that owns the translation.
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
