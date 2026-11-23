<?php

namespace Webkul\Measurement\Listeners;

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

class WarnMissingMeasurementConfigAfterCreate
{
    public function __construct(
        protected AttributeMeasurementRepository $attributeMeasurementRepository,
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Remind the admin to set the measurement family and default unit, which
     * the create form does not collect, so products can store a value.
     *
     * Flashed as `info` because the ajax form treats a flashed `warning` as a
     * rejected save and would keep the admin on the create page.
     *
     * @param  Attribute|null  $attribute
     */
    public function handle($attribute): void
    {
        if (request()->routeIs('admin.api.*')) {
            return;
        }

        if (
            ! $attribute
            || ! request()->hasSession()
            || ! $this->measurementHelper->isMeasurementBacked($attribute->type)
            || $this->attributeMeasurementRepository->getByAttributeId($attribute->id)
        ) {
            return;
        }

        request()->session()->flash('info', trans('measurement::app.messages.attribute.family_unit_required'));
    }
}
