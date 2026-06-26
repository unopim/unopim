<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class MeasurementUnitTranslation extends Model implements HistoryAuditable
{
    use HistoryTrait;

    /**
     * Table name associated with the model.
     */
    protected $table = 'measurement_unit_translations';

    /**
     * This model does not use timestamps.
     */
    public $timestamps = false;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'measurement_unit_id',
        'locale',
        'label',
    ];

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
