<?php

namespace Webkul\ProductPassport\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\DataGrid;
use Webkul\Publication\Enums\PublicationStatus;

class PublicationDataGrid extends DataGrid implements ExportableInterface
{
    public function prepareQueryBuilder(): Builder
    {
        $queryBuilder = DB::table('publications')
            ->leftJoin('products', 'publications.product_id', '=', 'products.id')
            ->leftJoin('channels', 'publications.channel_id', '=', 'channels.id')
            ->where('publications.type', 'dpp')
            ->select(
                'publications.id',
                'publications.uuid',
                'publications.status as publication_status',
                'publications.live_locale_count',
                'publications.last_published_at',
                'products.sku',
                'channels.code as channel_code',
            );

        $this->addFilter('id', 'publications.id');
        $this->addFilter('uuid', 'publications.uuid');
        $this->addFilter('publication_status', 'publications.status');
        $this->addFilter('sku', 'products.sku');
        $this->addFilter('channel_code', 'channels.code');

        return $queryBuilder;
    }

    public function prepareColumns(): void
    {
        $this->addColumn([
            'index'      => 'uuid',
            'label'      => trans('passport::app.publications.datagrid.uuid'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('passport::app.publications.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'channel_code',
            'label'      => trans('passport::app.publications.datagrid.channel'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'publication_status',
            'label'      => trans('passport::app.publications.datagrid.status'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'closure'    => fn ($row): string => e(trans(PublicationStatus::from($row->publication_status)->label())),
        ]);

        $this->addColumn([
            'index'      => 'live_locale_count',
            'label'      => trans('passport::app.publications.datagrid.live-locales'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'last_published_at',
            'label'      => trans('passport::app.publications.datagrid.last-published'),
            'type'       => 'datetime',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.passport.withdraw')) {
            $this->addAction([
                'index'  => 'withdraw',
                'icon'   => 'icon-cancel',
                'title'  => trans('passport::app.publications.datagrid.withdraw'),
                'method' => 'POST',
                'url'    => fn ($row): string => route('admin.catalog.passports.withdraw', $row->id),
            ]);
        }
    }

    /**
     * `live_locale_count`/`last_published_at` are plain, indexed columns —
     * sorting them is an ordinary indexed sort, not a per-row correlated
     * subquery.
     */
    public function getExportableData(array $parameters = []): array
    {
        return $this->queryBuilder->orderBy('publications.id')->lazyById(1000, 'publications.id')->collect()->all();
    }
}
