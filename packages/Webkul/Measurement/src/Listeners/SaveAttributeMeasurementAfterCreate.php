<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class SaveAttributeMeasurementAfterCreate
{
    public function __construct(
        protected AttributeMeasurementRepository $attributeMeasurementRepository,
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Save the measurement configuration the create form now collects.
     */
    public function handle(?Attribute $attribute): void
    {
        if (! $attribute || ! $this->measurementHelper->isMeasurementBacked($attribute->type)) {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            return;
        }

        $family = $this->measurementFamilyRepository->findOneByField('code', $familyCode);

        if (! $family) {
            throw ValidationException::withMessages([
                'measurement_family' => [trans('measurement::app.measurement.attribute.family_not_found')],
            ]);
        }

        if (! collect($family->units)->contains('code', $unitCode)) {
            throw ValidationException::withMessages([
                'measurement_unit' => [trans('measurement::app.measurement.attribute.unit_not_in_family')],
            ]);
        }

        $this->attributeMeasurementRepository->saveAttributeMeasurement($attribute->id, [
            'family_code' => $familyCode,
            'unit_code'   => $unitCode,
        ]);
    }
}
