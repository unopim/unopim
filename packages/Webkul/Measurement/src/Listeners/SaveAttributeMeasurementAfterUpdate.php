<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;

class SaveAttributeMeasurementAfterUpdate
{
    protected $attributeMeasurementRepository;

    public function __construct(
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    /**
     * Save the measurement configuration after the attribute has been updated.
     *
     * The attribute's own audit (and its translations) are created during the
     * update. We pin the measurement audit to that same timestamp so the audits
     * trigger assigns it the same version, keeping a single history entry.
     *
     * @param  Attribute  $attribute
     * @return void
     */
    public function handle($attribute)
    {
        if (! $attribute || $attribute->type !== 'measurement') {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            return;
        }

        $baseline = (int) request()->attributes->get('measurement_audit_baseline', 0);

        /**
         * The audit created for the attribute during this request (if any of its
         * own fields changed). Translations share the same version/timestamp, so
         * the latest audit above the baseline is enough to align with.
         */
        $attributeAudit = DB::table('audits')
            ->where('tags', 'attribute')
            ->where('history_id', $attribute->id)
            ->where('id', '>', $baseline)
            ->orderByDesc('id')
            ->first();

        if ($attributeAudit) {
            Carbon::setTestNow(Carbon::parse($attributeAudit->created_at));
        }

        try {
            $this->attributeMeasurementRepository->saveAttributeMeasurement($attribute->id, [
                'family_code' => $familyCode,
                'unit_code'   => $unitCode,
            ]);
        } finally {
            Carbon::setTestNow(null);
        }
    }
}
