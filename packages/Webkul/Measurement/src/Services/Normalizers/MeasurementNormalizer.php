<?php

namespace Webkul\Measurement\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Services\Normalizers\AbstractNormalizer;
use Webkul\Measurement\Repository\AttributeMeasurementRepository;

class MeasurementNormalizer extends AbstractNormalizer
{
    /**
     * @var AttributeMeasurementRepository
     */
    protected $attributeMeasurementRepository;

    public function __construct(AttributeMeasurementRepository $attributeMeasurementRepository)
    {
        $this->attributeMeasurementRepository = $attributeMeasurementRepository;
    }

    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        $format = $options['format'] ?? 'default';

        switch ($format) {
            case 'datagrid':
                return $this->datagridFormat($data, $attribute, $options);

            default:
                return $data;
        }
    }

    /**
     * Format the data for datagrid display.
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
        $unitLabel = $unitData['label'] ?? $unitData['name'] ?? $unitCode;

        if (is_array($unitLabel)) {
            $unitLabel = $unitLabel[$locale] ?? current($unitLabel) ?? $unitCode;
        }

        return $formattedAmount.' '.$unitLabel;
    }

    /**
     * Format the numeric amount: trim trailing zeros, keep at least one decimal.
     */
    protected function formatAmount($value): string
    {
        $num = (float) $value;

        if (floor($num) == $num) {
            return (string) (int) $num;
        }

        $formatted = rtrim(rtrim(number_format($num, 4, '.', ''), '0'), '.');

        if (strpos($formatted, '.') !== false) {
            $parts = explode('.', $formatted);
            if (strlen($parts[1]) === 1) {
                $formatted .= '0';
            }
        }

        return $formatted;
    }
}
