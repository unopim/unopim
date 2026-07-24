<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Contracts\MeasurementFamilyTranslation as MeasurementFamilyTranslationContract;

#[Fillable([
    'measurement_family_id',
    'locale',
    'label',
])]
#[Table(name: 'measurement_family_translations')]
#[WithoutTimestamps]
class MeasurementFamilyTranslation extends Model implements HistoryAuditable, MeasurementFamilyTranslationContract
{
    use HistoryTrait;

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
     *
     * @return BelongsTo<MeasurementFamily, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(MeasurementFamily::class, 'measurement_family_id');
    }
}
