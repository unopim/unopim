<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;

class PriceNormalizer extends AbstractNormalizer implements AttributeNormalizerInterface
{
    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        $format = $options['format'] ?? 'default';

        switch ($format) {
            case 'datagrid':
                return $this->datagridFormat($data, $options);
            default:
                return $data;
        }
    }

    /**
     * Format the data for datagrid.
     */
    protected function datagridFormat(mixed $data, array $options = [])
    {
        $format = [];

        if (! is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            $format[] = core()->currencySymbol($key).' '.$value;
        }

        return implode(', ', $format);
    }
}
