<?php

namespace Webkul\Measurement\Repository;

use Webkul\Core\Eloquent\Repository;
use Webkul\Measurement\Models\MeasurementFamily;

class MeasurementFamilyRepository extends Repository
{
    public function model(): string
    {
        return MeasurementFamily::class;
    }

    public function getUnitsByFamilyCode(?string $familyCode): array
    {
        if (! $familyCode) {
            return [];
        }

        $family = $this->findOneWhere(['code' => $familyCode]);

        return $family?->units_array ?? [];
    }
}
