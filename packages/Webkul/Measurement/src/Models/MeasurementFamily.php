<?php

namespace Webkul\Measurement\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Measurement\Database\Factories\MeasurementFamilyFactory;

class MeasurementFamily extends Model implements HistoryAuditable
{
    use HasFactory, HistoryTrait;

    /**
     * Mass assignable attributes.
     *
     * `labels` and `units` are NOT real columns anymore - they are virtual
     * write attributes kept for backward compatibility. They are intercepted
     * on save and persisted into the normalized translation/unit tables.
     */
    protected $fillable = [
        'code',
        'name',
        'standard_unit',
        'symbol',
        'labels',
        'units',
    ];

    protected $historyTags = ['Measurement Family'];

    protected $historyColumns = [
        'code',
        'name',
        'standard_unit',
        'symbol',
    ];

    /**
     * Virtual `labels`/`units` values captured during save() and flushed into
     * the normalized tables once the family row has been persisted.
     */
    protected array $pendingNormalized = [];

    /**
     * Register model events that translate the legacy `labels`/`units` write
     * attributes into the normalized tables.
     */
    protected static function booted(): void
    {
        static::saving(function (MeasurementFamily $family) {
            foreach (['labels', 'units'] as $key) {
                if (array_key_exists($key, $family->attributes)) {
                    $family->pendingNormalized[$key] = $family->attributes[$key];

                    unset($family->attributes[$key]);
                }
            }
        });

        static::saved(function (MeasurementFamily $family) {
            $family->flushNormalized();
        });
    }

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
     * Get the per-locale translations for the family.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(MeasurementFamilyTranslation::class, 'measurement_family_id');
    }

    /**
     * Get the units that belong to the family (ordered).
     */
    public function units(): HasMany
    {
        return $this->hasMany(MeasurementUnit::class, 'measurement_family_id')
            ->orderBy('position');
    }

    /**
     * Backward-compatible accessor: expose the family labels as a
     * [locale => label] map, matching the old JSON column shape.
     */
    public function getLabelsAttribute(): array
    {
        return $this->translations()->pluck('label', 'locale')->toArray();
    }

    /**
     * Backward-compatible accessor: rebuild the old `units` JSON array shape
     * from the normalized unit/translation/conversion tables so that every
     * existing consumer (helpers, observers, presenters, services) keeps
     * working without changes.
     */
    public function getUnitsAttribute(): array
    {
        return $this->units()
            ->with(['translations', 'conversions'])
            ->get()
            ->map(fn (MeasurementUnit $unit) => $unit->toLegacyArray())
            ->values()
            ->toArray();
    }

    /**
     * Alias of the `units` accessor kept for legacy callers.
     */
    public function getUnitsArrayAttribute(): array
    {
        return $this->units;
    }

    /**
     * Persist the captured `labels`/`units` write attributes into the
     * normalized tables. `units` uses full-replace semantics to mirror the
     * old behaviour of overwriting the entire JSON column.
     */
    public function flushNormalized(): void
    {
        if (array_key_exists('labels', $this->pendingNormalized)) {
            $labels = $this->pendingNormalized['labels'];

            if (is_array($labels)) {
                foreach ($labels as $locale => $label) {
                    $this->syncTranslation($this->translations(), $locale, $label);
                }
            }

            unset($this->pendingNormalized['labels']);
        }

        if (array_key_exists('units', $this->pendingNormalized)) {
            $units = $this->pendingNormalized['units'];

            $this->syncUnits(is_array($units) ? $units : []);

            unset($this->pendingNormalized['units']);
        }
    }

    /**
     * Full-replace the family units (and their translations/conversions) from
     * the legacy units array shape.
     */
    protected function syncUnits(array $units): void
    {
        $keptCodes = [];

        foreach (array_values($units) as $position => $unitData) {
            if (empty($unitData['code'])) {
                continue;
            }

            $code = $unitData['code'];
            $keptCodes[] = $code;

            $unit = $this->units()->updateOrCreate(
                ['code' => $code],
                [
                    'symbol'   => $unitData['symbol'] ?? null,
                    'position' => $position,
                ]
            );

            foreach (($unitData['labels'] ?? []) as $locale => $label) {
                $this->syncTranslation($unit->translations(), $locale, $label);
            }

            if (array_key_exists('convert_from_standard', $unitData)) {
                $this->syncConversions($unit, (array) $unitData['convert_from_standard']);
            }
        }

        // Delete removed units via model instances (not a mass delete) so the
        // `deleted` model event fires and the change is audited as history.
        $this->units()
            ->when(
                ! empty($keptCodes),
                fn ($query) => $query->whereNotIn('code', $keptCodes)
            )
            ->get()
            ->each
            ->delete();
    }

    /**
     * Sync a unit's ordered conversion steps with minimal churn: existing steps
     * are updated in place (only when operator/value actually change), new steps
     * created, and surplus steps removed. This keeps history clean - editing a
     * unit only audits conversion rows that genuinely changed instead of wiping
     * and recreating every step on each save.
     */
    protected function syncConversions(MeasurementUnit $unit, array $conversions): void
    {
        $conversions = array_values($conversions);

        $existing = $unit->conversions()->get()->values();

        foreach ($conversions as $index => $conversion) {
            if (! is_array($conversion)) {
                continue;
            }

            $operator = $conversion['operator'] ?? 'mul';
            $value = isset($conversion['value']) ? (string) $conversion['value'] : null;

            $current = $existing->get($index);

            if ($current) {
                $current->operator = $operator;
                $current->value = $value;
                $current->position = $index;

                if ($current->isDirty()) {
                    $current->save();
                }
            } else {
                $unit->conversions()->create([
                    'operator' => $operator,
                    'value'    => $value,
                    'position' => $index,
                ]);
            }
        }

        // Remove conversion steps that are no longer present.
        foreach ($existing as $index => $current) {
            if ($index >= count($conversions)) {
                $current->delete();
            }
        }
    }

    /**
     * Upsert a single translation row, but never persist an empty label.
     * An empty (null/'') value removes any existing translation for the locale,
     * so empty locales neither pollute the data nor surface as blank history rows.
     */
    protected function syncTranslation(HasMany $relation, string $locale, $label): void
    {
        if (filled($label)) {
            $relation->updateOrCreate(['locale' => $locale], ['label' => $label]);

            return;
        }

        $relation->where('locale', $locale)->get()->each->delete();
    }
}
