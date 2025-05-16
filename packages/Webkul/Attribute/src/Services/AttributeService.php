<?php

namespace Webkul\Attribute\Services;

use Illuminate\Support\Collection;
use Webkul\Attribute\Contracts\Attribute;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeService
{
    private array $cachedAttributes = [];

    private array $cachedAttributeRules = [];

    private array $nonExistingCodes = [];

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
    public function findByCodes(array $codes): ?array
    {
        $codesToFetch = array_diff($codes, array_keys($this->cachedAttributes), array_keys($this->nonExistingCodes));

        $attributes = [];

        if (! empty($codesToFetch)) {
            $attributes = $this->attributeRepository
                ->whereIn('code', $codesToFetch)
                ->get();
        }

        $codesToFetch = array_flip($codesToFetch);

        foreach ($attributes as $attribute) {
            $attrCode = $attribute['code'];
            $this->cachedAttributes[$attrCode] = $attribute;

            unset($codesToFetch[$attrCode]);
        }

        if (! empty($codesToFetch)) {
            $this->nonExistingCodes = array_merge($this->nonExistingCodes, $codesToFetch);
        }

        return array_intersect_key($this->cachedAttributes, array_flip($codes));
    }
}
