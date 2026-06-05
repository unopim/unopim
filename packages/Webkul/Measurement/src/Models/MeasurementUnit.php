<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;

class MeasurementUnit extends Model implements HistoryAuditable
{
    use HistoryTrait;

    /**
     * Table name associated with the model.
     */
    protected $table = 'measurement_units';

    /**
     * Mass assignable attributes.
     */
    protected $fillable = [
        'measurement_family_id',
        'code',
        'symbol',
        'position',
    ];

    /**
     * Group unit history under the parent measurement family entity.
     */
    protected $historyTags = ['Measurement Family'];

    /**
     * Only audit the meaningful unit fields (not the ordering position).
     */
    protected $auditInclude = [
        'code',
        'symbol',
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
     * Get the measurement family that owns the unit.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(MeasurementFamily::class, 'measurement_family_id');
    }

    /**
     * Get the per-locale translations for the unit.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MeasurementUnitTranslation::class, 'measurement_unit_id');
    }

    /**
     * Get the ordered conversion steps for the unit.
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(MeasurementUnitConversion::class, 'measurement_unit_id')
            ->orderBy('position');
    }

    /**
     * Get the per-locale label map ([locale => label]).
     */
    public function getLabelsAttribute(): array
    {
        return $this->translations->pluck('label', 'locale')->toArray();
    }

    /**
     * Build the legacy unit array shape consumed across the package:
     * ['code', 'labels' => [locale => label], 'symbol', 'convert_from_standard' => [['value','operator'], ...]].
     */
    public function toLegacyArray(): array
    {
        return [
            'code'                  => $this->code,
            'labels'                => $this->labels,
            'symbol'                => $this->symbol,
            'convert_from_standard' => $this->conversions
                ->map(fn ($conversion) => [
                    'value'    => $conversion->value,
                    'operator' => $conversion->operator,
                ])
                ->values()
                ->toArray(),
        ];
    }
}
