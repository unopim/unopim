<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Product\Repositories\AssociationTypeRepository;

class AssociationTypeDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected AssociationTypeRepository $associationTypeRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return Builder The query builder for the association type repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('code', [
            '=',
            'IN',
            'NOT IN',
        ]);

        $this->addFilter('status', ['=']);

        return $this->associationTypeRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted association type data.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(fn ($data) => $this->normalize($data), $paginator['data'] ?? []);
    }

    /**
     * Get association type by its code.
     *
     * @param  string  $code  The unique code of the association type.
     * @return array An associative array containing the association type's details.
     *
     * @throws ModelNotFoundException If an association type with the given code is not found.
     */
    public function getByCode($code)
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

        $associationType = $this->queryBuilder->first()?->toArray();

        if (! $associationType) {
            throw new ModelNotFoundException(
                sprintf('Association type with code %s could not be found.', (string) $code)
            );
        }

        return $this->normalize($associationType);
    }

    /**
     * Retrieves the custom fields defined on a given association type.
     *
     * @param  string  $code  The unique code of the association type.
     * @return array An array of associative arrays, each describing one field.
     *
     * @throws ModelNotFoundException If an association type with the given code is not found.
     */
    public function getFieldsByTypeCode($code)
    {
        $associationType = $this->associationTypeRepository->findByCode($code);

        if (! $associationType) {
            throw new ModelNotFoundException(
                sprintf('Association type with code %s could not be found.', (string) $code)
            );
        }

        $fields = $associationType->fields()->with('translations')->orderBy('position')->get()->toArray();

        return array_map(fn ($data) => [
            'code'             => $data['code'],
            'type'             => $data['type'],
            'status'           => (bool) $data['status'],
            'validation'       => $data['validation'],
            'position'         => $data['position'],
            'is_required'      => (int) $data['is_required'],
            'is_unique'        => (int) $data['is_unique'],
            'value_per_locale' => (int) $data['value_per_locale'],
            'section'          => $data['section'],
            'labels'           => $this->getTranslations($data),
        ], $fields);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(array $data): array
    {
        return [
            'code'            => $data['code'],
            'status'          => (bool) $data['status'],
            'position'        => $data['position'],
            'is_user_defined' => $data['is_user_defined'],
            'labels'          => $this->getTranslations($data),
        ];
    }
}
