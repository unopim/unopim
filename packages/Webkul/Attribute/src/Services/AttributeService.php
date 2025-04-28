<?php

namespace Webkul\Attribute\Services;

use Illuminate\Support\Collection;
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
        $alreadyExistingCodes = array_intersect($codes, array_keys($this->cachedAttributes));
        $codesToFetch = array_diff($codes, $alreadyExistingCodes);

        $attributes = collect();

        if (! empty($codesToFetch)) {
            $attributes = $this->attributeRepository
                ->whereIn('code', $codesToFetch)
                ->orderByRaw("FIELD(code, '".implode("', '", $codesToFetch)."')")
                ->get();
        }

        foreach ($attributes as $attribute) {
            $this->cachedAttributes[$attribute->code] = $attribute;
        }

        $attributes = $attributes->merge(array_intersect_key($this->cachedAttributes, array_flip($alreadyExistingCodes)))->keyBy('code');

        return $attributes;
    }
}
