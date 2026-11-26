<?php

namespace Webkul\Measurement\Listeners;

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

class SaveAttributeMeasurementAfterCreate
{
    public function __construct(
        protected AttributeMeasurementRepository $attributeMeasurementRepository,
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Save the measurement family and unit collected by the quick-create modal.
     *
     * The family and unit are validated in the "before" listener, so here the
     * values are already known good and only the save remains.
     */
    public function handle(?Attribute $attribute): void
    {
        if (! request()->hasSession()) {
            return;
        }

        if (! $attribute instanceof Attribute || ! $this->measurementHelper->isMeasurementBacked($attribute->type)) {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            return;
        }

        $this->attributeMeasurementRepository->saveAttributeMeasurement($attribute->id, [
            'family_code' => $familyCode,
            'unit_code'   => $unitCode,
        ]);
    }
}
