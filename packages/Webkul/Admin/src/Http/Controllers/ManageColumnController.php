<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\VueJsSelect\SelectOptionsController;

class ManageColumnController extends SelectOptionsController
{
    /**
     * Get a list of available default values for columns
     */
    public function getDefaultColumns(string $source = 'product')
    {
        switch ($source) {
            case 'product':
                return [[
                    'code'  => 'type',
                    'label' => trans('admin::app.catalog.products.index.datagrid.type'),
                ], [
                    'code'  => 'status',
                    'label' => trans('admin::app.catalog.products.index.datagrid.status'),
                ], [
                    'code'  => 'attribute_family',
                    'label' => trans('admin::app.catalog.products.index.datagrid.attribute-family'),
                ], [
                    'code'  => 'parent',
                    'label' => trans('admin::app.catalog.products.index.datagrid.parent'),
                ], [
                    'code'  => 'product_id',
                    'label' => trans('admin::app.catalog.products.index.datagrid.id'),
                ], [
                    'code'  => 'created_at',
                    'label' => trans('admin::app.catalog.products.index.datagrid.created-at'),
                ], [
                    'code'  => 'updated_at',
                    'label' => trans('admin::app.catalog.products.index.datagrid.updated-at'),
                ],
                ];
            default:
                return [];
        }
    }

    /**
     * get available columns
     */
    public function availableColumns()
    {
        $entityName = request()->get('entityName');
        $source = request()->get('source', 'product');
        $page = request()->get('page');
        $limit = request()->get('limit', self::DEFAULT_PER_PAGE);
        $query = request()->get('query') ?? '';
        $queryParams = request()->except(['page', 'query', 'entityName']);

        $options = $this->getOptionsByParams($entityName, $page, $query, $queryParams, $limit);
        $currentLocaleCode = core()->getRequestedLocaleCode();
        $formattedOptions = $page == 1 ? $this->getDefaultColumns($source) : [];

        foreach ($options as $option) {
            $formattedOptions[] = $this->formatOption($option, $currentLocaleCode);
        }

        return new JsonResponse([
            'options'  => $formattedOptions,
            'page'     => $options->currentPage(),
            'lastPage' => $options->lastPage(),
        ]);

    }

    /**
     * {@inheritdoc}
     */
    protected function formatOption(Model $option, string $currentLocaleCode)
    {
        $translatedOptionLabel = $this->getTranslatedLabel($currentLocaleCode, $option);

        return [
            'code'  => $option->code,
            'label' => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
        ];
    }
}
