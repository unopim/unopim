<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

class SaveAttributeMeasurementAfterUpdate
{
    public function __construct(protected AttributeMeasurementRepository $attributeMeasurementRepository) {}

    /**
     * Save the measurement configuration after the attribute has been updated.
     *
     * The attribute's own audit (and its translations) are created during the
     * update. We pin the measurement audit to that same timestamp so the audits
     * trigger assigns it the same version, keeping a single history entry.
     *
     * @param  Attribute  $attribute
     */
    public function handle($attribute): void
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

        $attributeAudit = DB::table('audits')
            ->where('tags', 'attribute')
            ->where('history_id', $attribute->id)
            ->where('id', '>', $baseline)
            ->orderByDesc('id')
            ->first();

        if ($attributeAudit) {
            Date::setTestNow(Date::parse($attributeAudit->created_at));
        }

        try {
            $this->attributeMeasurementRepository->saveAttributeMeasurement($attribute->id, [
                'family_code' => $familyCode,
                'unit_code'   => $unitCode,
            ]);
        } finally {
            Date::setTestNow();
        }
    }
}
