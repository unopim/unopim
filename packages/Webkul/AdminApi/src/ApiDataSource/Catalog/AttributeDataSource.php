<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Attribute\Repositories\AttributeColumnRepository;
use Webkul\Attribute\Repositories\AttributeRepository;

class AttributeDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeColumnRepository $attributeColumnRepository,
    ) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the attribute repository.
     */
    public function prepareApiQueryBuilder()
    {
        $this->addFilter('code', [
            '=',
            'IN',
            'NOT IN',
        ]);

        $this->addFilter('type', [
            '=',
            'IN',
            'NOT IN',
        ]);

        return $this->attributeRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted attribute data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {
            return [
                'code'              => $data['code'],
                'type'              => $data['type'],
                'validation'        => $data['validation'],
                'regex_pattern'     => $data['regex_pattern'],
                'position'          => $data['position'],
                'is_required'       => $data['is_required'],
                'is_unique'         => $data['is_unique'],
                'value_per_locale'  => $data['value_per_locale'],
                'value_per_channel' => $data['value_per_channel'],
                'enable_wysiwyg'    => $data['enable_wysiwyg'],
                'labels'            => $this->getTranslations($data),
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get attribute by its code.
     *
     * @param  string  $code  The unique code of the attribute.
     * @return array An associative array containing the attribute's code, status, and label.
     *
     * @throws ModelNotFoundException If a attribute with the given code is not found.
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

        $attribute = $this->queryBuilder->first()?->toArray();

        if (! $attribute) {
            throw new ModelNotFoundException(
                trans('admin::app.catalog.attributes.not-found', ['code' => $code])
            );
        }

        return [
            'code'              => $attribute['code'],
            'type'              => $attribute['type'],
            'validation'        => $attribute['validation'],
            'regex_pattern'     => $attribute['regex_pattern'],
            'position'          => $attribute['position'],
            'is_required'       => $attribute['is_required'],
            'is_unique'         => $attribute['is_unique'],
            'value_per_locale'  => $attribute['value_per_locale'],
            'value_per_channel' => $attribute['value_per_channel'],
            'enable_wysiwyg'    => $attribute['enable_wysiwyg'],
            'labels'            => $this->getTranslations($attribute),
        ];
    }

    /**
     * Retrieves attribute options by the attribute's code.
     *
     * @param  string  $attributeCode  The unique code of the attribute.
     * @return array An array of attribute options, each containing the option's code, sort order, and labels.
     */
    public function getOptionsByAttributeCode(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        $attributeOption = $attribute?->options()?->orderBy('sort_order')->get()->toArray();

        return array_map(function ($data) {
            return [
                'code'       => $data['code'],
                'sort_order' => $data['sort_order'],
                'labels'     => $this->getTranslations($data, 'label'),
            ];
        }, $attributeOption ?? []);
    }

    /**
     * Retrieves attribute columns by the attribute's code.
     *
     * @param  string  $attributeCode  The unique code of the attribute.
     * @return array An array of attribute columns, each containing the column's code, sort order, type, validation, and labels.
     */
    public function getColumnsByAttributeCode(string $attributeCode)
    {
        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);
        $attributeColumn = $attribute?->columns()?->orderBy('sort_order')->get()->toArray();

        return array_map(function ($data) {
            return [
                'code'       => $data['code'],
                'sort_order' => $data['sort_order'],
                'type'       => $data['type'],
                'validation' => $data['validation'],
                'labels'     => $this->getTranslations($data, 'label'),
            ];
        }, $attributeColumn ?? []);
    }

    /**
     * Retrieves attribute column options by the attribute's code and column's code.
     *
     * @param  string  $attributeCode  The unique code of the attribute.
     * @param  string  $columnCode  The unique code of the column.
     * @return array An array of attribute column options, each containing the option's code, and labels.
     */
    public function getColumnOptionByColumnCode(string $attributeCode, string $columnCode)
    {
        if (empty($attributeCode) || empty($columnCode)) {
            return response()->json([
                'message' => trans('admin::app.catalog.attributes.column.code-required'),
            ], 422);
        }

        $attribute = $this->attributeRepository->findOneByField('code', $attributeCode);

        if (! $attribute) {
            return response()->json([
                'message' => trans('admin::app.catalog.attributes.not-found', ['code' => $attributeCode]),
            ], 404);
        }

        $column = $this->attributeColumnRepository->findOneWhere([
            'attribute_id' => $attribute->id,
            'code'         => $columnCode,
        ]);

        if (! $column) {
            return response()->json([
                'message' => trans('admin::app.catalog.attributes.column.not-found', ['code' => $columnCode]),
            ], 404);
        }

        if (! in_array($column->type, ['select', 'multiselect'], true)) {
            return response()->json([
                'message' => trans('admin::app.catalog.attributes.invalid-column-type'),
            ], 422);
        }

        $options = $column->options()
            ->with('translations')
            ->get()
            ->toArray();

        $mapped = array_map(function ($data) {
            return [
                'code'  => $data['code'],
                'label' => $this->getTranslations($data, 'label'),
            ];
        }, $options ?? []);

        return response()->json($mapped, 200);
    }
}
