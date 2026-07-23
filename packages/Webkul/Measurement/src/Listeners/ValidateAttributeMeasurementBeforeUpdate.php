<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;

class ValidateAttributeMeasurementBeforeUpdate
{
    public function __construct(protected AttributeRepository $attributeRepository, protected MeasurementFamilyRepository $measurementFamilyRepository) {}

    /**
     * Validate the measurement configuration before the attribute is updated.
     *
     * The actual save happens in the "after" listener so the measurement audit
     * can be grouped into the same history version as the attribute update.
     *
     * @param  int|string  $attributeId
     */
    public function handle($attributeId): void
    {
        $attribute = $this->attributeRepository->find($attributeId);

        if (! $attribute || $attribute->type !== 'measurement') {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            Session::flash('error', trans('measurement::app.messages.attribute.family_unit_required'));
            throw new HttpResponseException(
                back()->withInput()
            );
        }

        $familyExists = $this->measurementFamilyRepository->findWhere(['code' => $familyCode])->isNotEmpty();

        if (! $familyExists) {
            Session::flash('error', trans('measurement::app.messages.attribute.family_not_found'));
            throw new HttpResponseException(
                back()->withInput()
            );
        }

        request()->attributes->set(
            'measurement_audit_baseline',
            (int) DB::table('audits')->max('id')
        );
    }
}
