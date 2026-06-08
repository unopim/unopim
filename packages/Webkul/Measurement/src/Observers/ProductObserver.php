<?php

namespace Webkul\Measurement\Observers;

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;
use Webkul\Product\Models\Product;

class ProductObserver
{
    /**
     * Measurement helper instance.
     */
    protected $helper;

    /**
     * Attribute measurement repository instance.
     */
    protected $attributeMeasurementRepository;

    public function __construct(
        MeasurementHelper $helper,
        AttributeMeasurementRepository $attributeMeasurementRepository
    ) {
        $this->helper = $helper;
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    /**
     * Handle product saving event.
     *
     * @return void
     */
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

    /**
     * Process measurement values for all scopes.
     *
     * @return void
     */
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

    /**
     * Process measurement values for a single scope.
     *
     * @return void
     */
    protected function processScope(array &$scopedValues)
    {
        // 1. Preload all attributes in one go
        $attributes = app(AttributeRepository::class)
            ->findWhereIn('code', array_keys($scopedValues))
            ->keyBy('code');

        // 2. Cache measurement lookups
        $measurementCache = [];

        foreach ($scopedValues as $attributeCode => $value) {

            $attribute = $attributes[$attributeCode] ?? null;

            if (! $attribute || $attribute->type !== 'measurement' || ! is_array($value)) {
                continue;
            }

            // Skip already processed formats
            if (isset($value['amount']) || isset($value['<all_channels>'])) {
                continue;
            }

            if (! isset($value['value']) || $value['value'] === '' || $value['value'] === null) {
                unset($scopedValues[$attributeCode]);

                continue;
            }

            // 3. Cache measurement per attribute
            if (! isset($measurementCache[$attribute->id])) {
                $measurementCache[$attribute->id] =
                    $this->attributeMeasurementRepository->getByAttributeId($attribute->id);
            }

            $measurement = $measurementCache[$attribute->id];

            if ($measurement && $measurement->family) {

                $family = $measurement->family;

                // Reuse the shared helper so the unit -> base conversion is done in
                // ONE place (reverse + invert of convert_from_standard). This avoids
                // the previous forward-direction bug that stored wrong base_data.
                $baseData = $this->helper->calculateBaseValue(
                    $value['value'],
                    $value['unit'] ?? null,
                    $family
                );

                $scopedValues[$attributeCode] = [
                    'unit'      => $value['unit'] ?? null,
                    'amount'    => number_format((float) $value['value'], 4, '.', ''),
                    'family'    => $family->code,
                    'base_data' => number_format((float) $baseData, 6, '.', ''),
                    'base_unit' => $family->standard_unit,
                ];
            }
        }
    }
}
