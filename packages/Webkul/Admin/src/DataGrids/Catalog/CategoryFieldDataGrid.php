<?php

namespace Webkul\Admin\DataGrids\Catalog;

use Illuminate\Support\Facades\DB;
use Webkul\Category\Models\CategoryField;
use Webkul\DataGrid\DataGrid;

class CategoryFieldDataGrid extends DataGrid
{
    /**
     * Default sort column
     */
    protected $sortColumn = 'status';

    /**
     * Default sort order
     */
    protected $sortOrder = 'desc';

    protected array $typeConfig = [];

    /**
     * Initialize the config value
     */
    public function __construct()
    {
        $this->typeConfig = config('category_field_types');
    }

    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('category_fields')
            ->leftJoin('category_field_translations as requested_category_field_translation', function ($leftJoin) {
                $leftJoin->on('requested_category_field_translation.category_field_id', '=', 'category_fields.id')
                    ->where('requested_category_field_translation.locale', core()->getRequestedLocaleCode());

            })
            ->select(
                'category_fields.id',
                'code',
                'type',
                'is_required',
                'is_unique',
                'value_per_locale',
                'status',
                'position',
                'created_at',
                'requested_category_field_translation.name as name'
            );

        $this->addFilter('name', 'requested_category_field_translation.name');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.catalog.category_fields.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'name',
            'label'      => trans('admin::app.catalog.category_fields.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => function ($row) {

                if (! empty($row->name)) {
                    return $row->name;
                }

                $categoryField = CategoryField::with('translations')->find($row->id);

                $requestedLocale = core()->getRequestedLocaleCode();
                $fallbackName = null;

                foreach ($categoryField->translations as $translation) {
                    if ($translation->locale === $requestedLocale && ! empty($translation->name)) {
                        return $translation->name;
                    }

                    if (! empty($translation->name) && $fallbackName === null) {
                        $fallbackName = $translation->name;
                    }
                }

                return $fallbackName ?: "[{$row->code}]";
            },
        ]);

        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('admin::app.catalog.category_fields.index.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type' => 'basic',

                'params' => [
                    'options' => [
                        [
                            'label' => trans('admin::app.catalog.category_fields.index.datagrid.activated'),
                            'value' => 1,
                        ], [
                            'label' => trans('admin::app.catalog.category_fields.index.datagrid.disabled'),
                            'value' => 0,
                        ],
                    ],
                ],
            ],
            'closure'    => fn ($row) => $row->status
                ? '<span class="label-active">'.trans('admin::app.catalog.category_fields.index.datagrid.activated').'</span>'
                : '<span class="label-info">'.trans('admin::app.catalog.category_fields.index.datagrid.disabled').'</span>',
        ]);

        $this->addColumn([
            'index'      => 'position',
            'label'      => trans('admin::app.catalog.category_fields.index.datagrid.position'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'type',
            'label'      => trans('admin::app.catalog.category_fields.index.datagrid.type'),
            'type'       => 'string',
            'searchable' => true,
            'closure'    => fn ($row) => trans($this->typeConfig[$row->type]['name'] ?? "[$row->type]"),
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('catalog.category_fields.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'index'  => 'edit',
                'title'  => trans('admin::app.catalog.category_fields.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.catalog.category_fields.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('catalog.category_fields.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.catalog.category_fields.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.catalog.category_fields.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare mass actions.
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('catalog.category_fields.delete')) {
            $this->addMassAction([
                'icon'    => 'icon-delete',
                'title'   => trans('admin::app.catalog.category_fields.index.datagrid.delete'),
                'method'  => 'POST',
                'url'     => route('admin.catalog.category_fields.mass_delete'),
                'options' => ['actionType' => 'delete'],
            ]);
        }

        if (bouncer()->hasPermission('catalog.category_fields.edit')) {
            $this->addMassAction([
                'icon'    => 'icon-edit',
                'title'   => trans('admin::app.catalog.category_fields.index.datagrid.update-status'),
                'url'     => route('admin.catalog.category_fields.mass_update'),
                'method'  => 'POST',
                'options' => [
                    [
                        'label' => trans('admin::app.catalog.category_fields.index.datagrid.active'),
                        'value' => 1,
                    ], [
                        'label' => trans('admin::app.catalog.category_fields.index.datagrid.disable'),
                        'value' => 0,
                    ],
                ],
            ]);
        }
    }
}
