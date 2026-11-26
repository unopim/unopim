<?php

namespace Webkul\Measurement\Listeners;

use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
use Webkul\Measurement\Traits\RejectsInvalidMeasurementConfig;

class ValidateAttributeMeasurementBeforeCreate
{
    use RejectsInvalidMeasurementConfig;

    public function __construct(
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Validate the measurement configuration before the attribute is created.
     *
     * Validating here, rather than in the "after" listener, stops the attribute
     * from being committed with a bad family or unit. Otherwise the row is left
     * behind and the resubmit fails on the unique attribute code.
     */
    public function handle(): void
    {
        if (! request()->hasSession()) {
            return;
        }

        if (! $this->measurementHelper->isMeasurementBacked(request('type'))) {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            return;
        }

        $this->rejectUnlessUnitInFamily((string) $familyCode, (string) $unitCode);
    }
}
