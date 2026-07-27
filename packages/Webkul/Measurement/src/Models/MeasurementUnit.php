<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Contracts\MeasurementUnit as MeasurementUnitContract;

#[Fillable([
    'measurement_family_id',
    'code',
    'symbol',
    'position',
])]
#[Table(name: 'measurement_units')]
class MeasurementUnit extends Model implements HistoryAuditable, MeasurementUnitContract
{
    use HistoryTrait;

    /**
     * Group unit history under the parent measurement family entity.
     */
    protected $historyTags = ['Measurement'];

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
     *
     * @return BelongsTo<MeasurementFamily, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(MeasurementFamily::class, 'measurement_family_id');
    }

    /**
     * Get the per-locale translations for the unit.
     *
     * @return HasMany<MeasurementUnitTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MeasurementUnitTranslation::class, 'measurement_unit_id');
    }

    /**
     * Get the ordered conversion steps for the unit.
     *
     * @return HasMany<MeasurementUnitConversion, $this>
     */
    public function conversions(): HasMany
    {
        return $this->hasMany(MeasurementUnitConversion::class, 'measurement_unit_id')
            ->orderBy('position');
    }

    /**
     * Get the per-locale label map ([locale => label]).
     */
    protected function labels(): Attribute
    {
        return Attribute::make(get: fn () => $this->translations->pluck('label', 'locale')->toArray());
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
                ->map(fn ($conversion): array => [
                    'value'    => $conversion->value,
                    'operator' => $conversion->operator,
                ])
                ->values()
                ->toArray(),
        ];
    }
}
