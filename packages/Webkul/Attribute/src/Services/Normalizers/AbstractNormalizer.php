<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizerInterface;

abstract class AbstractNormalizer implements AttributeNormalizerInterface
{
    /**
     * Retrieves the attribute data.
     * It should be implemented by subclasses
     * to provide specific behaviour.
     */
    abstract public function getData(mixed $data, ?Attribute $attribute = null, array $options = []);

    /**
     * Normalize the given attribute value.
     */
    public function normalize(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        return $this->getData($data, $attribute, $options);
    }
}
