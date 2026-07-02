<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\Attribute;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AttributeMeasurement extends Model implements HistoryAuditable
{
    use HistoryTrait;

    /**
     * Table name associated with the model.
     */
    protected $table = 'attribute_measurement';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'attribute_id',
        'family_code',
        'unit_code',
    ];

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
     * @return BelongsTo
     */
    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * Get the related measurement family.
     *
     * @return BelongsTo
     */
    public function family()
    {
        return $this->belongsTo(
            MeasurementFamily::class,
            'family_code',
            'code'
        );
    }

    /**
     * Get selected unit details from family units.
     *
     * @return array|null
     */
    public function getUnitAttribute()
    {
        if (! $this->family) {
            return null;
        }

        return collect($this->family->units_array)
            ->firstWhere('id', $this->unit_code);
    }
}
