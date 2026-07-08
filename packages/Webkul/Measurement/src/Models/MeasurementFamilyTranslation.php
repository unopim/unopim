<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class MeasurementFamilyTranslation extends Model implements HistoryAuditable
{
    use HistoryTrait;

    /**
     * Table name associated with the model.
     */
    protected $table = 'measurement_family_translations';

    /**
     * This model does not use timestamps.
     */
    public $timestamps = false;

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'measurement_family_id',
        'locale',
        'label',
    ];

    /**
     * Group label history under the parent measurement family entity.
     */
    protected $historyTags = ['Measurement'];

    /**
     * Only audit the translated label.
     */
    protected $auditInclude = ['label'];

    /**
     * Render the label change as a per-locale history row.
     */
    protected $historyTranslatableFields = [
        'label' => 'Label',
    ];

    /**
     * Group history versions under the related measurement family id.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) $this->measurement_family_id;
    }

    /**
     * Get the measurement family that owns the translation.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(MeasurementFamily::class, 'measurement_family_id');
    }
}
