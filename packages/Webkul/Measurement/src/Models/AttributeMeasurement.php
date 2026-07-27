<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\Attribute;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Contracts\AttributeMeasurement as AttributeMeasurementContract;

#[Fillable([
    'attribute_id',
    'family_code',
    'unit_code',
])]
#[Table(name: 'attribute_measurement')]
class AttributeMeasurement extends Model implements AttributeMeasurementContract, HistoryAuditable
{
    use HistoryTrait;

    /**
     * Tag the history under the "attribute" entity so measurement changes
     * show up on the related attribute's history tab.
     */
    protected $historyTags = ['attribute'];

    /**
     * Only audit the measurement configuration fields.
     */
    protected $auditInclude = [
        'family_code',
        'unit_code',
    ];

    /**
     * Group history versions under the related attribute id instead of this
     * pivot record's own id.
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return (int) $this->attribute_id;
    }

    /**
     * Get the related attribute.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * Get the related measurement family.
     *
     * @return BelongsTo<MeasurementFamily, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(
            MeasurementFamily::class,
            'family_code',
            'code'
        );
    }

    /**
     * Get selected unit details from family units.
     */
    protected function unit(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function () {
            if (! $this->family) {
                return null;
            }

            return collect($this->family->units_array)
                ->firstWhere('id', $this->unit_code);
        });
    }
}
