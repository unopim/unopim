<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizer as NormalizerContract;

abstract class AbstractNormalizer implements NormalizerContract
{
    abstract public function getData(mixed $data, ?Attribute $attribute = null, array $options = []);

    /**
     * Normalize the given attribute value.
     */
    public function normalize(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        return $this->getData($data, $attribute, $options);
    }
}
