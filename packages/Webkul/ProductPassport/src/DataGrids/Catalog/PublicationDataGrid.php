<?php

namespace Webkul\ProductPassport\DataGrids\Catalog;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\DataGrid;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Services\Gs1DigitalLink;

class PublicationDataGrid extends DataGrid implements ExportableInterface
{
    /**
     * `status_code` duplicates the status column on purpose: column closures run
     * before action conditions and overwrite `publication_status` with its
     * translated label, so the raw enum value has to survive under a second alias.
     */
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
                'publications.status as status_code',
                'publications.live_locale_count',
                'publications.last_published_at',
                'publications.gtin',
                'publications.alias_identifier as gs1_link',
                'products.sku',
                'channels.code as channel_code',
            )
            // Single correlated aggregate, not a per-row query: the sum of every daily counter for this publication.
            ->selectSub(
                DB::table('publication_view_stats')
                    ->selectRaw('COALESCE(SUM(views), 0)')
                    ->whereColumn('publication_view_stats.publication_id', 'publications.id'),
                'views',
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

        $this->addColumn([
            'index'      => 'views',
            'label'      => trans('passport::app.publications.datagrid.views'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'gtin',
            'label'      => trans('passport::app.publications.datagrid.gtin'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'gs1_link',
            'label'      => trans('passport::app.publications.datagrid.gs1-link'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => $this->gs1Link($row),
        ]);

        $this->addColumn([
            'index'      => 'public_url',
            'label'      => trans('passport::app.publications.datagrid.public-url'),
            'type'       => 'string',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => fn ($row): string => $this->publicUrl($row),
        ]);
    }

    public function prepareActions(): void
    {
        if (bouncer()->hasPermission('catalog.passport.view')) {
            $this->addAction([
                'index'  => 'versions',
                'icon'   => 'icon-view',
                'title'  => trans('passport::app.publications.datagrid.version-history'),
                'method' => 'GET',
                'url'    => fn ($row): string => route('admin.catalog.passports.versions', $row->id),
            ]);
        }

        if (bouncer()->hasPermission('catalog.passport.withdraw')) {
            $this->addAction([
                'index'     => 'withdraw',
                'icon'      => 'icon-cancel',
                'title'     => trans('passport::app.publications.datagrid.withdraw'),
                'method'    => 'POST',
                'url'       => fn ($row): string => route('admin.catalog.passports.withdraw', $row->id),
                'condition' => fn ($row): bool => $row->status_code === PublicationStatus::Published->value,
            ]);

            $this->addAction([
                'index'     => 'reinstate',
                'icon'      => 'icon-done',
                'title'     => trans('passport::app.publications.datagrid.reinstate'),
                'method'    => 'POST',
                'url'       => fn ($row): string => route('admin.catalog.passports.reinstate', $row->id),
                'condition' => fn ($row): bool => $row->status_code === PublicationStatus::Withdrawn->value,
            ]);
        }
    }

    public function prepareMassActions(): void
    {
        if (bouncer()->hasPermission('catalog.passport.publish')) {
            $this->addMassAction([
                'title'  => trans('passport::app.publications.datagrid.mass-publish'),
                'url'    => route('admin.catalog.passports.bulk-publish'),
                'method' => 'POST',
            ]);
        }

        if (bouncer()->hasPermission('catalog.passport.withdraw')) {
            $this->addMassAction([
                'title'   => trans('passport::app.publications.datagrid.mass-transition'),
                'url'     => route('admin.catalog.passports.mass_transition'),
                'method'  => 'POST',
                'options' => [
                       [
                           'label' => trans('passport::app.publications.datagrid.withdraw'),
                           'value' => PublicationStatus::Withdrawn->value,
                       ], [
                           'label' => trans('passport::app.publications.datagrid.reinstate'),
                           'value' => PublicationStatus::Published->value,
                       ],
                ],
            ]);
        }
    }

    /**
     * `live_locale_count`/`last_published_at` are plain, indexed columns —
     * sorting them is an ordinary indexed sort, not a per-row correlated
     * subquery.
     *
     * `public_url` is a closure column, so the CSV writer (which reads raw row
     * properties, not the grid's formatter) never sees it unless stamped here.
     * `status_code` goes the other way: it exists only for the action conditions,
     * and the writer derives its header row from the row's keys, so leaving it in
     * would add a duplicate status column to every export.
     */
    public function getExportableData(array $parameters = []): array
    {
        return $this->queryBuilder
            ->orderBy('publications.id')
            ->lazyById(1000, 'publications.id')
            ->map(function (object $row): object {
                $row->public_url = $this->publicUrl($row);
                $row->gs1_link = $this->gs1Link($row);

                unset($row->status_code);

                return $row;
            })
            ->collect()
            ->all();
    }

    /**
     * The GS1 Digital Link the printed carrier resolves through, rebuilt from the
     * current base url rather than read from the alias stamped at publish time —
     * the column and the QR code must never disagree about where a scan lands.
     */
    private function gs1Link(object $row): string
    {
        $gs1 = resolve(Gs1DigitalLink::class);

        if ($row->gs1_link === null || ! $gs1->isWellFormed($row->gtin)) {
            return '';
        }

        return $gs1->build((string) $row->gtin, $row->channel_code ?? null);
    }

    /**
     * The passport's public landing URL, keyed to its channel's configured base
     * host (`general.publication.settings.base_url`) so a merchant can feed it to
     * their own label/print system; falls back to the app URL when unset.
     */
    private function publicUrl(object $row): string
    {
        $base = core()->getConfigData('general.publication.settings.base_url', $row->channel_code ?? null)
            ?: config('app.url');

        return rtrim((string) $base, '/').route('publication.public.dpp.show', ['uuid' => $row->uuid], false);
    }
}
