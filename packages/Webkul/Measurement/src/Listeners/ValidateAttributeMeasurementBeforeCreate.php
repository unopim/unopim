<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Session;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class ValidateAttributeMeasurementBeforeCreate
{
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
        if (request()->routeIs('admin.api.*')) {
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

        $family = $this->measurementFamilyRepository->findOneByField('code', $familyCode);

        if (! $family) {
            Session::flash('error', trans('measurement::app.messages.attribute.family_not_found'));

            throw new HttpResponseException(
                back()->withInput()
            );
        }

        if (! collect($family->units)->contains('code', $unitCode)) {
            Session::flash('error', trans('measurement::app.messages.attribute.unit_not_in_family'));

            throw new HttpResponseException(
                back()->withInput()
            );
        }
    }
}
