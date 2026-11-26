<?php

namespace Webkul\Measurement\Listeners;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
use Webkul\Measurement\Traits\RejectsInvalidMeasurementConfig;

class ValidateAttributeMeasurementBeforeUpdate
{
    use RejectsInvalidMeasurementConfig;

    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected MeasurementFamilyRepository $measurementFamilyRepository,
        protected MeasurementHelper $measurementHelper
    ) {}

    /**
     * Validate the measurement configuration before the attribute is updated.
     *
     * The actual save happens in the "after" listener so the measurement audit
     * can be grouped into the same history version as the attribute update.
     */
    public function handle(int|string $attributeId): void
    {
        if (! request()->hasSession()) {
            return;
        }

        $attribute = $this->attributeRepository->find($attributeId);

        if (! $attribute || ! $this->measurementHelper->isMeasurementBacked($attribute->type)) {
            return;
        }

        $familyCode = request('measurement_family');
        $unitCode = request('measurement_unit');

        if (! $familyCode || ! $unitCode) {
            $this->rejectMeasurementConfig('measurement::app.messages.attribute.family_unit_required');
        }

        $this->rejectUnlessUnitInFamily((string) $familyCode, (string) $unitCode);

        request()->attributes->set(
            'measurement_audit_baseline',
            (int) DB::table('audits')->max('id')
        );
    }
}
