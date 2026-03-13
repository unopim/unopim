<?php

namespace Webkul\Measurement\Helpers;

use Webkul\Measurement\Repository\AttributeMeasurementRepository;

class MeasurementHelper
{
    protected $attributeMeasurementRepository;

    public function __construct(
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    public function isMeasurementAttribute($attribute)
    {
        return $this->attributeMeasurementRepository
            ->getByAttributeId($attribute->id) !== null;
    }
}
