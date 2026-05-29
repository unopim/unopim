<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;

class AttributeFamilyDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     */
    public function __construct(
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeGroupRepository $attributeGroupRepository
    ) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return Builder The query builder for the attribute  familyrepository.
     */
    public function prepareApiQueryBuilder(): mixed
    {
        $this->addFilter('code', [
            '=',
            'IN',
            'NOT IN',
        ]);

        return $this->attributeFamilyRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted attribute family data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    #[\Override]
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(fn (mixed $data) => [
            'code'             => $data['code'],
            'labels'           => $this->getTranslations($data),
            'attribute_groups' => $this->getFamilyGroups($data),
        ], $paginator['data'] ?? []);
    }

    /**
     * Get attribute family by its code.
     *
     * @param  string  $code  The unique code of the attribute family.
     * @return array An associative array containing the attribute family's code, status, and label.
     *
     * @throws ModelNotFoundException If a attribute family with the given code is not found.
     */
    public function getByCode(string $code): array
    {
        $this->prepareForSingleData();

        $requestedFilters = [
            'code' => [
                [
                    'operator' => '=',
                    'value'    => $code,
                ],
            ],
        ];

        $this->queryBuilder = $this->processRequestedFilters($requestedFilters);

        $attributeFamily = $this->queryBuilder->first()?->toArray();

        if (! $attributeFamily) {
            throw new ModelNotFoundException(
                sprintf('Attribute Family with code %s could not be found.', $code)
            );
        }

        return [
            'code'             => $attributeFamily['code'],
            'labels'           => $this->getTranslations($attributeFamily),
            'attribute_groups' => $this->getFamilyGroups($attributeFamily),
        ];
    }

    /**
     * Retrieves a list of attribute groups associated with a given attribute family.
     */
    public function getFamilyGroups(array $family): array
    {
        if (! isset($family['attribute_family_group_mappings']) || empty($family['attribute_family_group_mappings'])) {
            return [];
        }

        return array_map(function (mixed $groupMapping) {
            $group = $this->getGroup($groupMapping['attribute_group_id']); // TODO: need to improve this use wouth repository

            return [
                'code'              => $group['code'],
                'position'          => $groupMapping['position'],
                'custom_attributes' => $this->getAttributes($groupMapping['custom_attributes'] ?? []),
            ];
        }, $family['attribute_family_group_mappings'] ?? []);
    }

    /**
     * Retrieves an attribute group by its ID.
     *
     * @param  int  $id  The unique identifier of the attribute group.
     */
    public function getGroup(int $id): array
    {
        return $this->attributeGroupRepository->find($id)->toArray();
    }

    /**
     * Retrieves a list of custom attributes for an attribute group.
     */
    public function getAttributes(array $attributes): array
    {
        return array_map(fn (mixed $attribute) => [
            'code'     => $attribute['code'],
            'position' => $attribute['pivot']['position'],
        ], $attributes);
    }
}
