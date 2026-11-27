<?php

namespace Webkul\Measurement\Traits;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

/**
 * Shared family/unit check for the attribute create and update listeners, which
 * stop the save with a flashed error when the submitted pair is invalid.
 *
 * @property MeasurementFamilyRepository $measurementFamilyRepository
 */
trait RejectsInvalidMeasurementConfig
{
    /**
     * Stop the request unless the family exists and holds the given unit.
     */
    protected function rejectUnlessUnitInFamily(string $familyCode, string $unitCode): void
    {
        $family = $this->measurementFamilyRepository->findOneByField('code', $familyCode);

        if (! $family) {
            $this->rejectMeasurementConfig('measurement::app.messages.attribute.family_not_found');
        }

        if (! collect($family->units)->contains('code', $unitCode)) {
            $this->rejectMeasurementConfig('measurement::app.messages.attribute.unit_not_in_family');
        }
    }

    /**
     * Flash the given error and send the admin back to the form with their input.
     */
    protected function rejectMeasurementConfig(string $translationKey): never
    {
        Session::flash('error', trans($translationKey));

        throw new HttpResponseException(back()->withInput());
    }
}
