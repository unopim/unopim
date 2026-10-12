<?php

namespace Webkul\Measurement\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Services\Normalizers\AbstractNormalizer;
use Webkul\Measurement\Repositories\AttributeMeasurementRepository;

class MeasurementNormalizer extends AbstractNormalizer
{
    public function __construct(
        /**
         * Attribute measurement repository instance.
         */
        protected AttributeMeasurementRepository $attributeMeasurementRepository
    ) {}

    /**
     * Normalize attribute value based on format type.
     *
     * @return mixed
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        $format = $options['format'] ?? 'default';

        return match ($format) {
            'datagrid' => $this->datagridFormat($data, $attribute, $options),
            default    => $data,
        };
    }

    /**
     * Format measurement data for datagrid display.
     *
     * @param  mixed  $attribute
     * @return mixed
     */
    protected function datagridFormat(mixed $data, $attribute, array $options = [])
    {
        if (! is_array($data)) {
            return $data;
        }

        $amount = null;
        $unitCode = null;

        if (isset($data['<all_channels>']['<all_locales>'])) {
            $localeData = $data['<all_channels>']['<all_locales>'];
            $amount = $localeData['amount'] ?? null;
            $unitCode = $localeData['unit'] ?? null;
        } elseif (isset($data['amount']) && isset($data['unit'])) {
            $amount = $data['amount'];
            $unitCode = $data['unit'];
        } else {
            return $data;
        }

        if ($amount === null || $amount === '') {
            return '';
        }

        $formattedAmount = $this->formatAmount($amount);

        if (! $unitCode || ! $attribute) {
            return $formattedAmount;
        }

        $attributeMeasurement = $this->attributeMeasurementRepository->getByAttributeId($attribute->id);

        if (! $attributeMeasurement || ! $attributeMeasurement->family) {
            return $formattedAmount;
        }

        $units = $attributeMeasurement->family->units ?? [];
        $unitData = collect($units)->firstWhere('code', $unitCode);

        if (! $unitData) {
            return $formattedAmount;
        }

        $locale = $options['locale'] ?? app()->getLocale();
        $unitLabel = $unitData['labels'] ?? $unitData['name'] ?? $unitCode;

        if (is_array($unitLabel)) {
            $unitLabel = $unitLabel[$locale] ?? current($unitLabel) ?? $unitCode;
        }

        return $formattedAmount.' '.$unitLabel;
    }

    /**
     * Format numeric amount for display.
     *
     * Removes trailing zeros while preserving precision.
     *
     * @param  mixed  $value
     */
    protected function formatAmount($value): string
    {
        $num = (float) $value;

        if (floor($num) === $num) {
            return (string) (int) $num;
        }

        $formatted = rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');

        if (str_contains($formatted, '.')) {
            $parts = explode('.', $formatted);
            if (strlen($parts[1]) === 1) {
                $formatted .= '0';
            }
        }

        return $formatted;
    }
}
