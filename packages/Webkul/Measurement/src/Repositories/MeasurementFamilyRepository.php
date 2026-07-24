<?php

namespace Webkul\Measurement\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Measurement\Contracts\MeasurementFamily;

class MeasurementFamilyRepository extends Repository
{
    /**
     * Get model class name.
     */
    public function model(): string
    {
        return MeasurementFamily::class;
    }

    /**
     * Get measurement units (legacy array shape) by family code.
     */
    public function getUnitsByFamilyCode(?string $familyCode): array
    {
        if (! $familyCode) {
            return [];
        }

        $family = $this->findOneWhere(['code' => $familyCode]);

        return $family?->units_array ?? [];
    }

    /**
     * Create a full measurement family from the legacy data shape:
     * ['code', 'name', 'standard_unit', 'symbol', 'labels' => [locale => label],
     *  'units' => [['code', 'labels' => [...], 'symbol', 'convert_from_standard' => [...]], ...]].
     *
     * The `labels`/`units` keys are persisted into the normalized tables by the
     * MeasurementFamily model's save events.
     */
    public function createFamily(array $data): MeasurementFamily
    {
        return $this->create($data);
    }
}
