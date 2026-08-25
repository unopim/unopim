<?php

namespace Webkul\Measurement\Tests\Support;

use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreExporter;

class ChannelScopeMeasurementSpyExporter extends CoreExporter
{
    public array $extractMeasurementInputs = [];

    protected function extractMeasurement(mixed $value): array
    {
        $this->extractMeasurementInputs[] = $value;

        return parent::extractMeasurement($value);
    }
}
