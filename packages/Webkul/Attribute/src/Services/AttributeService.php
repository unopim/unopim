<?php

namespace Webkul\Attribute\Services;

use Illuminate\Support\Facades\DB;
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
     * Get Attribute list by search query
     */
    public function getAttributeListBySearch(string $search, array $columns = ['*']): array
    {
        return DB::table('attributes')
            ->select($columns)
            ->leftJoin('attribute_translations as attribute_name', function ($join) {
                $join->on('attribute_name.attribute_id', '=', 'attributes.id')
                    ->where('attribute_name.locale', '=', core()->getRequestedLocaleCode());
            })
            ->where(function ($query) use ($search) {
                $query->where('attributes.code', 'LIKE', '%'.$search.'%')
                    ->orWhere('attribute_name.name', 'LIKE', '%'.$search.'%');
            })
            ->get()
            ->toArray();
    }
}
