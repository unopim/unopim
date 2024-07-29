<?php

namespace Webkul\Attribute\Services;

use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeService
{
    private array $cachedAttributes = [];

    private array $cachedAttributeRules = [];

    /**
     * Create service object
     */
    public function __construct(private AttributeRepository $attributeRepository) {}

    /**
     * Get Attribute object throught attribute code
     */
    public function findAttributeByCode(string $code): ?Attribute
    {
        if (isset($this->cachedAttributes[$code])) {
            return $this->cachedAttributes[$code];
        }

        $attribute = $this->attributeRepository->findOneByField('code', $code);

        if ($attribute) {
            $this->cachedAttributes[$code] = $attribute;
        }

        return $attribute;
    }
}
