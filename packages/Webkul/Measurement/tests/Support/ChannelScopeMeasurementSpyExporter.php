<?php

namespace Webkul\Measurement\Tests\Support;

use Webkul\Measurement\Helpers\Exporters\ProductExporter;

class ChannelScopeMeasurementSpyExporter extends ProductExporter
{
    public array $extractMeasurementInputs = [];

    protected function extractMeasurement(mixed $value): array
    {
        $this->extractMeasurementInputs[] = $value;

        return parent::extractMeasurement($value);
    }
}
