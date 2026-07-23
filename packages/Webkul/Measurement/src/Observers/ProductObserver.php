<?php

namespace Webkul\Measurement\Observers;

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;
use Webkul\Product\Models\Product;

class ProductObserver
{
    public function __construct(
        /**
         * Measurement helper instance.
         */
        protected MeasurementHelper $helper,
        /**
         * Attribute measurement repository instance.
         */
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Handle product saving event.
     */
    public function saving(Product $product): void
    {
        if (is_null($product->values)) {
            return;
        }

        $values = $product->values ?? [];

        if (! is_array($values)) {
            $values = [];
        }

        $this->validateRequiredMeasurements($values);

        $this->processMeasurementValues($values);

        $product->values = $values;
    }

    /**
     * Ensure required measurement attributes are not submitted empty.
     *
     * The generic "required" rule applied on product values treats a measurement
     * value as present as long as the wrapping array exists (e.g. ['value' => '',
     * 'unit' => 'cm']). That lets a required measurement be saved without an actual
     * value, so the emptiness has to be checked against the nested "value" key here.
     *
     * @return void
     */
    protected function validateRequiredMeasurements(array $values)
    {
        $scopes = $this->collectScopes($values);

        if ($scopes === []) {
            return;
        }

        $codes = [];

        foreach ($scopes as $scope) {
            $codes = array_merge($codes, array_keys($scope));
        }

        $codes = array_values(array_unique($codes));

        if ($codes === []) {
            return;
        }

        $attributes = resolve(AttributeRepository::class)
            ->findWhereIn('code', $codes)
            ->keyBy('code');

        $errors = [];

        foreach ($scopes as $scope) {
            foreach ($scope as $attributeCode => $value) {
                $attribute = $attributes[$attributeCode] ?? null;
                if (! $attribute) {
                    continue;
                }
                if ($attribute->type !== 'measurement') {
                    continue;
                }
                if (! $attribute->is_required) {
                    continue;
                }

                if ($this->isMeasurementValueEmpty($value)) {
                    $errors[$attributeCode] = trans('validation.required', [
                        'attribute' => $attribute->name ?: $attribute->code,
                    ]);
                }
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Flatten the scoped product values into a list of attributeCode => value maps.
     *
     * @return array<int, array>
     */
    protected function collectScopes(array $values): array
    {
        $scopes = [];

        foreach ($values as $scope => $scopedValues) {
            if (! is_array($scopedValues)) {
                continue;
            }

            if ($scope === 'common') {
                $scopes[] = $scopedValues;
            } elseif ($scope === 'locale_specific' || $scope === 'channel_specific') {
                foreach ($scopedValues as $innerValues) {
                    if (is_array($innerValues)) {
                        $scopes[] = $innerValues;
                    }
                }
            } elseif ($scope === 'channel_locale_specific') {
                foreach ($scopedValues as $channelValues) {
                    if (! is_array($channelValues)) {
                        continue;
                    }

                    foreach ($channelValues as $localeValues) {
                        if (is_array($localeValues)) {
                            $scopes[] = $localeValues;
                        }
                    }
                }
            }
        }

        return $scopes;
    }

    /**
     * Determine whether a submitted measurement value is empty.
     */
    protected function isMeasurementValueEmpty($value): bool
    {
        if (! is_array($value)) {
            return $value === '' || $value === null;
        }

        if (isset($value['amount']) && $value['amount'] !== '' && $value['amount'] !== null) {
            return false;
        }

        return ! isset($value['value']) || $value['value'] === '' || $value['value'] === null;
    }

    /**
     * Process measurement values for all scopes.
     *
     * @return void
     */
    protected function processMeasurementValues(array &$values)
    {

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
        $attributes = resolve(AttributeRepository::class)
            ->findWhereIn('code', array_keys($scopedValues))
            ->keyBy('code');

        $measurementCache = [];

        foreach ($scopedValues as $attributeCode => $value) {

            $attribute = $attributes[$attributeCode] ?? null;
            if (! $attribute) {
                continue;
            }
            if ($attribute->type !== 'measurement') {
                continue;
            }
            if (! is_array($value)) {
                continue;
            }
            if (isset($value['amount'])) {
                continue;
            }
            if (isset($value['<all_channels>'])) {
                continue;
            }

            if (! isset($value['value']) || $value['value'] === '' || $value['value'] === null) {
                unset($scopedValues[$attributeCode]);

                continue;
            }

            if (! isset($measurementCache[$attribute->id])) {
                $measurementCache[$attribute->id] =
                    $this->attributeMeasurementRepository->getByAttributeId($attribute->id);
            }

            $measurement = $measurementCache[$attribute->id];

            if ($measurement && $measurement->family) {

                $family = $measurement->family;

                $scopedValues[$attributeCode] = $this->helper->buildValueStructure(
                    $value['value'],
                    $value['unit'] ?? null,
                    $family->code,
                    $family
                );
            }
        }
    }
}
