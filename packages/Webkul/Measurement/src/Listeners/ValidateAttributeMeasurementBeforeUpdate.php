<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Models\MeasurementFamily;

class ValidateAttributeMeasurementBeforeUpdate
{
    protected $attributeRepository;

    public function __construct(
        AttributeRepository $attributeRepository
    ) {
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Validate the measurement configuration before the attribute is updated.
     *
     * The actual save happens in the "after" listener so the measurement audit
     * can be grouped into the same history version as the attribute update.
     *
     * @param  int|string  $attributeId
     * @return void
     */
    public function handle($attributeId)
    {
        $attribute = $this->attributeRepository->find($attributeId);

        if (! $attribute || $attribute->type !== 'measurement') {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            Session::flash('error', 'Measurement Family and Unit are required.');
            throw new HttpResponseException(
                redirect()->back()->withInput()
            );
        }

        $familyExists = MeasurementFamily::where('code', $familyCode)->exists();

        if (! $familyExists) {
            Session::flash('error', 'Selected Measurement Family does not exist.');
            throw new HttpResponseException(
                redirect()->back()->withInput()
            );
        }

        /**
         * Remember the latest audit id before the attribute update runs, so the
         * after-listener can detect the audit created for the attribute during
         * this same request and align the measurement audit to its version.
         */
        request()->attributes->set(
            'measurement_audit_baseline',
            (int) DB::table('audits')->max('id')
        );
    }
}
