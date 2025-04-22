<?php

namespace Webkul\Attribute\Services;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * Retrieves a collection of Attribute objects based on the provided attribute codes.
     */
    public function findByCodes(array $codes): ?Collection
    {
        $attributes = $this->attributeRepository
            ->whereIn('code', $codes)
            ->orderByRaw("FIELD(code, '".implode("', '", $codes)."')")
            ->get();

        foreach ($attributes as $attribute) {
            $this->cachedAttributes[$attribute->code] = $attribute;
        }

        return $attributes;
    }
}
