<?php

namespace Webkul\Admin\DataGrids\Settings;

use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class ChannelDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $requestedLocaleCode = core()->getRequestedLocaleCode();

        $fallbackLocaleCode = app()->getFallbackLocale();

        $tableCategories = DB::table('categories')->select('id', 'code', 'additional_data->locale_specific->'.$requestedLocaleCode.'->name as name');

        $tablePrefix = DB::getTablePrefix();

        $queryBuilder = DB::table('channels')
            ->leftJoin('channel_translations as requested_channel_translation', function ($leftJoin) use ($requestedLocaleCode) {
                $leftJoin->on('requested_channel_translation.channel_id', '=', 'channels.id')
                    ->where('requested_channel_translation.locale', $requestedLocaleCode);

            })
            ->leftJoin('channel_translations as fallback_channel_translation', function ($leftJoin) use ($fallbackLocaleCode) {
                $leftJoin->on('fallback_channel_translation.channel_id', '=', 'channels.id')
                    ->where('fallback_channel_translation.locale', $fallbackLocaleCode);

            })
            ->leftJoinSub($tableCategories, 'categories', function ($leftJoin) {
                $leftJoin->on('categories.id', '=', 'channels.root_category_id');
            })
            ->select(
                'channels.id',
                'channels.code',
                'channels.root_category_id',
                DB::raw('(CASE WHEN CHAR_LENGTH(TRIM('.$tablePrefix.'requested_channel_translation.name)) < 1 THEN '.$tablePrefix.'fallback_channel_translation.name ELSE '.$tablePrefix.'requested_channel_translation.name END) as translated_name'),
                DB::raw("(CASE WHEN ".$tablePrefix."categories.name IS NOT NULL THEN REPLACE(".$tablePrefix."categories.name, '\"', '') ELSE CONCAT('[', ".$tablePrefix."categories.code, ']') END) as translated_category_name")
            );

        $this->addFilter('id', 'channels.id');
        $this->addFilter('code', 'channels.code');
        $this->addFilter('translated_name', 'requested_channel_translation.name');
        $this->addFilter('translated_category_name', DB::raw("CASE WHEN ".$tablePrefix."categories.name IS NOT NULL THEN REPLACE(".$tablePrefix."categories.name, '\"', '') ELSE CONCAT('[', ".$tablePrefix."categories.code, ']') END"));

        return $queryBuilder;
    }

    public function prepareColumns()
    {
        $this->addColumn([
            'index'      => 'id',
            'label'      => trans('admin::app.settings.channels.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'code',
            'label'      => trans('admin::app.settings.channels.index.datagrid.code'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'translated_name',
            'label'      => trans('admin::app.settings.channels.index.datagrid.name'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'translated_category_name',
            'label'      => trans('admin::app.settings.channels.index.datagrid.root-category'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);
    }

    public function prepareActions()
    {
        if (bouncer()->hasPermission('settings.channels.edit')) {
            $this->addAction([
                'icon'   => 'icon-edit',
                'title'  => trans('admin::app.settings.channels.index.datagrid.edit'),
                'method' => 'GET',
                'url'    => function ($row) {
                    return route('admin.settings.channels.edit', $row->id);
                },
            ]);
        }

        if (bouncer()->hasPermission('settings.channels.delete')) {
            $this->addAction([
                'icon'   => 'icon-delete',
                'title'  => trans('admin::app.settings.channels.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('admin.settings.channels.delete', $row->id);
                },
            ]);
        }
    }
}
