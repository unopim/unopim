<?php

namespace Webkul\Attribute\Services\Normalizers;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Contracts\AttributeNormalizer as NormalizerContract;

class DefaultNormalizer extends AbstractNormalizer implements NormalizerContract
{
    /**
     * Normalize the given attribute value.
     */
    public function getData(mixed $data, ?Attribute $attribute = null, array $options = [])
    {
        return $data;
    }
}
