<?php

namespace Webkul\Webhook\DataGrids;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;

class LogsDataGrid extends DataGrid
{
    /**
     * Search Placeholder
     *
     * @var string
     */
    protected $searchPlaceholder = 'admin::app.components.datagrid.toolbar.search_by.code';

    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('webhook_logs')->select(
            'id',
            'created_at',
            'sku',
            'user',
            'status',
            'extra'
        );

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
            'index'      => 'id',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.id'),
            'type'       => 'integer',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => true,
        ]);

        $this->addColumn([
            'index'      => 'created_at',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.created_at'),
            'type'       => 'date_range',
            'searchable' => false,
            'filterable' => false,
            'sortable'   => false,
            'closure'    => function ($row) {
                $timezone = auth('admin')->user()->timezone ?? config('app.timezone');

                try {
                    $display = Carbon::parse($row->created_at)->setTimezone($timezone)->toDateTimeString();
                } catch (\Exception $e) {
                    $display = $row->created_at;
                }

                return '<span class="icon-calendar"></span> '.$display;
            },
        ]);

        $this->addColumn([
            'index'      => 'sku',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.sku'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => false,
        ]);

        $this->addColumn([
            'index'      => 'user',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.user'),
            'type'       => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable'   => true,
        ]);
        $this->addColumn([
            'index'      => 'status',
            'label'      => trans('webhook::app.configuration.webhook.logs.index.datagrid.status'),
            'type'       => 'dropdown',
            'searchable' => false,
            'filterable' => true,
            'sortable'   => true,
            'options'    => [
                'type'   => 'basic',
                'params' => [
                    'options' => $this->buildStatusFilterOptions(),
                ],
            ],
            'closure' => function ($row) {
                $extra = is_string($row->extra ?? null) ? json_decode($row->extra, true) : ($row->extra ?? []);
                $code = $extra['response']['status'] ?? null;

                if ($row->status) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.success');

                    if (is_numeric($code) && (int) $code > 0) {
                        $label .= ' ('.(int) $code.')';
                    }

                    return '<span class="break-words label-completed">'.$label.'</span>';
                }

                if (! is_numeric($code) || (int) $code === 0) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.timeout_or_error');
                } elseif ((int) $code >= 500) {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.server_error').' ('.(int) $code.')';
                } else {
                    $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.failed').' ('.(int) $code.')';
                }

                return '<span class="break-words label-canceled">'.$label.'</span>';
            },
        ]);
    }

    /**
     * Build the status dropdown from the actual (status, http-code) pairs
     * present in webhook_logs so the filter mirrors the column badges.
     */
    protected function buildStatusFilterOptions(): array
    {
        // Laravel translates the `extra->response->status` JSON path to the
        // right operator per driver (JSON_UNQUOTE/JSON_EXTRACT on MySQL, ->>
        // on PostgreSQL), so this stays portable across both deployments.
        $rows = DB::table('webhook_logs')
            ->select('status', 'extra->response->status as code')
            ->distinct()
            ->get();

        $pairs = [];
        $hasTimeout = false;

        foreach ($rows as $row) {
            if ($row->code === null || ! is_numeric($row->code) || (int) $row->code === 0) {
                $hasTimeout = true;

                continue;
            }

            $pairs[] = [(int) $row->status, (int) $row->code];
        }

        usort($pairs, fn ($a, $b) => $b[0] <=> $a[0] ?: $a[1] <=> $b[1]);

        $options = [];

        foreach ($pairs as [$status, $code]) {
            if ($status === 1) {
                $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.success').' ('.$code.')';
            } elseif ($code >= 500) {
                $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.server_error').' ('.$code.')';
            } else {
                $label = trans('webhook::app.configuration.webhook.logs.index.datagrid.failed').' ('.$code.')';
            }

            $options[] = [
                'label' => $label,
                'value' => $status.':'.$code,
            ];
        }

        if ($hasTimeout) {
            $options[] = [
                'label' => trans('webhook::app.configuration.webhook.logs.index.datagrid.timeout_or_error'),
                'value' => 'timeout_or_error',
            ];
        }

        return $options;
    }

    /**
     * Translate each selected dropdown value into the matching SQL predicate.
     * Values are either "<status>:<code>" pairs (mirroring rows in the DB) or
     * the "timeout_or_error" sentinel for null/0 codes.
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        if (isset($requestedFilters['status'])) {
            $statusValues = $requestedFilters['status'];
            unset($requestedFilters['status']);

            $this->queryBuilder->where(function ($outer) use ($statusValues) {
                foreach ($statusValues as $value) {
                    $outer->orWhere(function ($q) use ($value) {
                        if ($value === 'timeout_or_error') {
                            $q->where('status', 0)->where(function ($inner) {
                                $inner->whereNull('extra->response->status')
                                    ->orWhere('extra->response->status', 0);
                            });

                            return;
                        }

                        if (! is_string($value) || ! preg_match('/^[01]:\d+$/', $value)) {
                            $q->whereRaw('1 = 0');

                            return;
                        }

                        [$status, $code] = explode(':', $value, 2);

                        $q->where('status', (int) $status)
                            ->where('extra->response->status', (int) $code);
                    });
                }
            });
        }

        return parent::processRequestedFilters($requestedFilters);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('configuration.webhook.logs.delete')) {
            $this->addAction([
                'index'  => 'delete',
                'icon'   => 'icon-delete',
                'title'  => trans('webhook::app.configuration.webhook.logs.index.datagrid.delete'),
                'method' => 'DELETE',
                'url'    => function ($row) {
                    return route('webhook.logs.delete', $row->id);
                },
            ]);
        }
    }

    /**
     * Prepare the mass actions
     *
     * @return void
     */
    public function prepareMassActions()
    {
        if (bouncer()->hasPermission('configuration.webhook.logs.mass_delete')) {
            $this->addMassAction([
                'title'   => trans('webhook::app.configuration.webhook.logs.index.datagrid.delete'),
                'url'     => route('webhook.logs.mass_delete'),
                'method'  => 'POST',
                'options' => ['actionType' => 'delete'],
            ]);
        }
    }
}
