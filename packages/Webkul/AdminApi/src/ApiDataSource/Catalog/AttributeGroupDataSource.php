<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Attribute\Repositories\AttributeGroupRepository;

class AttributeGroupDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected AttributeGroupRepository $attributeGroupRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the attribute group repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('code', [
            '=',
            'IN',
            'NOT IN',
        ]);

        return $this->attributeGroupRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted attribute group data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {
            return [
                'code'   => $data['code'],
                'labels' => $this->getTranslations($data),
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get attribute group by its code.
     *
     * @param  string  $code  The unique code of the attribute group.
     * @return array An associative array containing the attribute group's code, status, and label.
     *
     * @throws ModelNotFoundException If a attribute group with the given code is not found.
     */
    public function getByCode(string $code)
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

        $attributeGroup = $this->queryBuilder->first()?->toArray();

        if (! $attributeGroup) {
            throw new ModelNotFoundException(
                sprintf('attribute group with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'   => $attributeGroup['code'],
            'labels' => $this->getTranslations($attributeGroup),
        ];
    }
}
