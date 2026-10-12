<?php

namespace Webkul\Measurement\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Models\MeasurementFamilyTranslation;
use Webkul\Measurement\Models\MeasurementUnit;
use Webkul\Measurement\Models\MeasurementUnitConversion;
use Webkul\Measurement\Models\MeasurementUnitTranslation;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    protected $models = [
        MeasurementFamily::class,
        MeasurementFamilyTranslation::class,
        MeasurementUnit::class,
        MeasurementUnitTranslation::class,
        MeasurementUnitConversion::class,
        AttributeMeasurement::class,
    ];
}
