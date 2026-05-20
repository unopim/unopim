<?php

namespace Webkul\Measurement\Observers;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Product\Models\Product;

class ProductObserver
{
    protected $helper;

    protected $attributeMeasurementRepository;

    public function __construct(
        MeasurementHelper $helper,
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->helper = $helper;
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    public function saving(Product $product)
    {
        if (is_null($product->values)) {
            return;
        }

        $values = $product->values ?? [];

        if (! is_array($values)) {
            $values = [];
        }

        $this->processMeasurementValues($values);

        $product->values = $values;
    }

    protected function processMeasurementValues(array &$values)
    {

        if (empty($values)) {
            return;
        }

        foreach ($values as $scope => &$scopedValues) {

            if (! is_array($scopedValues)) {
                continue;
            }

            if ($scope === 'common') {
                $this->processScope($scopedValues);

            } elseif ($scope === 'locale_specific') {
                foreach ($scopedValues as &$localeValues) {
                    if (is_array($localeValues)) {
                        $this->processScope($localeValues);
                    }
                }

            } elseif ($scope === 'channel_specific') {
                foreach ($scopedValues as &$channelValues) {
                    if (is_array($channelValues)) {
                        $this->processScope($channelValues);
                    }
                }

            } elseif ($scope === 'channel_locale_specific') {
                foreach ($scopedValues as &$channelValues) {
                    if (! is_array($channelValues)) {
                        continue;
                    }

                    foreach ($channelValues as &$localeValues) {
                        if (is_array($localeValues)) {
                            $this->processScope($localeValues);
                        }
                    }
                }
            }
        }
    }

    protected function processScope(array &$scopedValues)
    {
        foreach ($scopedValues as $attributeCode => $value) {

            $attribute = app(AttributeRepository::class)
                ->findOneByField('code', $attributeCode);

            if ($attribute && $attribute->type === 'measurement' && is_array($value)) {

                // Skip if already in full format (has amount key from helper)
                if (isset($value['amount'])) {
                    continue;
                }

                // Skip if already in locale/channel format (has <all_channels> structure)
                if (isset($value['<all_channels>'])) {
                    continue;
                }

                if (! isset($value['value']) || $value['value'] === '' || $value['value'] === null) {
                    unset($scopedValues[$attributeCode]);

                    continue;
                }

                $measurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

                if ($measurement && $measurement->family) {
                    $family = $measurement->family;
                    $baseUnit = $family->standard_unit;

                    $baseData = $this->calculateBaseData(
                        $value['value'],
                        $value['unit'] ?? null,
                        $family
                    );

                    $scopedValues[$attributeCode] = [
                        'unit'      => $value['unit'] ?? null,
                        'amount'    => number_format((float) $value['value'], 4, '.', ''),
                        'family'    => $family->code,
                        'base_data' => number_format((float) $baseData, 6, '.', ''),
                        'base_unit' => $baseUnit,
                    ];
                }
            }
        }
    }

    protected function calculateBaseData($value, $unit, $family)
    {
        if (! $unit) {
            return $value;
        }

        $units = collect($family->units);
        $unitData = $units->firstWhere('code', $unit);

        if (! $unitData) {
            return $value;
        }

        $conversions = $unitData['convert_from_standard'] ?? [];
        $baseValue = (float) $value;

        foreach ($conversions as $conversion) {
            $op = $conversion['operator'] ?? null;
            $val = $conversion['value'] ?? null;

            if (! is_numeric($val)) {
                continue;
            }

            switch ($op) {
                case 'mul':
                    $baseValue *= $val;
                    break;
                case 'div':
                    if ($val != 0) {
                        $baseValue /= $val;
                    }
                    break;
                case 'add':
                    $baseValue += $val;
                    break;
                case 'sub':
                    $baseValue -= $val;
                    break;
            }
        }

        return $baseValue;
    }
}
