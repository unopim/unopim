<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Category\Repositories\CategoryFieldRepository;

class CategoryFieldDataSource extends ApiDataSource
{
    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(protected CategoryFieldRepository $categoryFieldRepository) {}

    /**
     * Prepares the query builder for API requests.
     *
     * @return \Illuminate\Database\Query\Builder The query builder for the CategoryField repository.
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

        return $this->categoryFieldRepository->queryBuilder();
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted category field data.
     *
     * @throws \Exception If the paginator data is not in the expected format.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        return array_map(function ($data) {

            return [
                'code'             => $data['code'],
                'type'             => $data['type'],
                'status'           => $data['status'],
                'validation'       => $data['validation'],
                'regex_pattern'    => $data['regex_pattern'],
                'position'         => $data['position'],
                'is_required'      => $data['is_required'],
                'is_unique'        => $data['is_unique'],
                'value_per_locale' => $data['value_per_locale'],
                'enable_wysiwyg'   => $data['enable_wysiwyg'],
                'section'          => $data['section'],
                'regex_pattern'    => $data['regex_pattern'],
                'labels'           => $this->getTranslations($data),
            ];
        }, $paginator['data'] ?? []);
    }

    /**
     * Get category field by its code.
     *
     * @param  string  $code  The unique code of the category field.
     * @return array An associative array containing the category field's code, status, and label.
     *
     * @throws ModelNotFoundException If a category field with the given code is not found.
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

        $categoryField = $this->queryBuilder->first()?->toArray();

        if (! $categoryField) {
            throw new ModelNotFoundException(
                sprintf('Category field with code %s could not be found.', (string) $code)
            );
        }

        return [
            'code'             => $categoryField['code'],
            'type'             => $categoryField['type'],
            'status'           => $categoryField['status'],
            'validation'       => $categoryField['validation'],
            'regex_pattern'    => $categoryField['regex_pattern'],
            'position'         => $categoryField['position'],
            'is_required'      => $categoryField['is_required'],
            'is_unique'        => $categoryField['is_unique'],
            'value_per_locale' => $categoryField['value_per_locale'],
            'enable_wysiwyg'   => $categoryField['enable_wysiwyg'],
            'section'          => $categoryField['section'],
            'regex_pattern'    => $categoryField['regex_pattern'],
            'labels'           => $this->getTranslations($categoryField),
        ];
    }

    /**
     * Retrieves the options associated with a specific category field.
     *
     * @param  string  $fieldCode  The unique code of the category field.
     * @return array An array of associative arrays, each containing the 'code', 'sort_order', and 'labels' of an option.
     *
     * @throws ModelNotFoundException If a category field with the given code is not found.
     */
    public function getOptionsByFieldCode($fieldCode)
    {
        $categoryField = $this->categoryFieldRepository->findOneByField('code', $fieldCode);
        if (! $categoryField) {
            throw new ModelNotFoundException(
                sprintf('Category field with code %s could not be found.', (string) $fieldCode)
            );
        }

        $fieldOption = $categoryField->options()->orderBy('sort_order')->get()->toArray();

        return array_map(function ($data) {
            return [
                'code'       => $data['code'],
                'sort_order' => $data['sort_order'],
                'labels'     => $this->getTranslations($data, 'label'),
            ];
        }, $fieldOption ?? []);
    }
}
