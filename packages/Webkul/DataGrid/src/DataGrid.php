<?php

namespace Webkul\DataGrid;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Webkul\Admin\Exports\DataGridExport;
use Webkul\DataGrid\Contracts\ExportableInterface;
use Webkul\DataGrid\Enums\ColumnTypeEnum;

abstract class DataGrid
{
    /**
     * Upper bound on the number of ids a single "select all matching" request will resolve.
     * Guards against unbounded payloads and stays within Elasticsearch's default result window.
     */
    const MASS_ACTION_ID_LIMIT = 10000;

    /**
     * Primary column.
     *
     * @var string
     */
    protected $primaryColumn = 'id';

    /**
     * Whether the grid offers the "select all matching records across pages" option.
     * Grids where a whole-result-set mass action is unsafe or meaningless can disable it.
     */
    protected bool $enableSelectAll = true;

    /**
     * Default sort column of datagrid.
     *
     * @var ?string
     */
    protected $sortColumn;

    /**
     * Default sort order of datagrid.
     *
     * @var string
     */
    protected $sortOrder = 'desc';

    /**
     * Default items per page.
     *
     * @var int
     */
    protected $itemsPerPage = 10;

    /**
     * Columns.
     *
     * @var array
     */
    protected $columns = [];

    /**
     * Actions.
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Mass action.
     *
     * @var array
     */
    protected $massActions = [];

    /**
     * Query builder instance.
     *
     * @var object
     */
    protected $queryBuilder;

    /**
     * Search Placeholder
     *
     * @var string
     */
    protected $searchPlaceholder = 'admin::app.components.datagrid.toolbar.search.title';

    /**
     * Paginator instance.
     */
    protected LengthAwarePaginator $paginator;

    /**
     * Exportable.
     */
    protected bool $exportable = false;

    /**
     * Every matching primary-key value for the current filter/search context.
     *
     * Populated only when the request asks for a "select all matching" id list,
     * so mass actions can act on the whole filtered set rather than one page.
     *
     * @var ?array<int, mixed>
     */
    protected ?array $massActionIds = null;

    /**
     * Export meta information.
     */
    protected mixed $exportFile = null;

    /**
     * Prepare query builder.
     */
    abstract public function prepareQueryBuilder();

    /**
     * Prepare columns.
     */
    abstract public function prepareColumns();

    /**
     * Prepare actions.
     */
    public function prepareActions() {}

    /**
     * Prepare mass actions.
     */
    public function prepareMassActions() {}

    /**
     * Get columns.
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * Get actions.
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * Get mass actions.
     */
    public function getMassActions(): array
    {
        return $this->massActions;
    }

    /**
     * Add column.
     */
    public function addColumn(array $column): void
    {
        $this->columns[] = new Column(
            index: $column['index'],
            label: $column['label'],
            type: $column['type'],
            options: $column['options'] ?? null,
            searchable: $column['searchable'],
            filterable: $column['filterable'],
            sortable: $column['sortable'],
            closure: $column['closure'] ?? null,
            visible: $column['visible'] ?? true,
            attribute_type: isset($column['attribute_type']) && is_string($column['attribute_type']) ? $column['attribute_type'] : null,
            operators: isset($column['operators']) && is_array($column['operators']) ? array_values($column['operators']) : null,
            filter_label: isset($column['filter_label']) && is_string($column['filter_label']) ? $column['filter_label'] : null,
            default_filter: (bool) ($column['default_filter'] ?? true),
            removable_filter: (bool) ($column['removable_filter'] ?? false),
        );
    }

    /**
     * Add action.
     */
    public function addAction(array $action): void
    {
        $this->actions[] = new Action(
            index: $action['index'] ?? '',
            icon: $action['icon'] ?? '',
            title: $action['title'],
            method: $action['method'],
            url: $action['url'],
            frontendView: $action['frontend_view'] ?? '',
            condition: $action['condition'] ?? null,
        );
    }

    /**
     * Add mass action.
     */
    public function addMassAction(array $massAction): void
    {
        $this->massActions[] = new MassAction(
            icon: $massAction['icon'] ?? '',
            title: $massAction['title'],
            method: $massAction['method'],
            url: $massAction['url'],
            options: $massAction['options'] ?? [],
        );
    }

    /**
     * Map your filter.
     */
    public function addFilter(string $datagridColumn, mixed $queryColumn): void
    {
        foreach ($this->columns as $column) {
            if ($column->index === $datagridColumn) {
                $column->setDatabaseColumnName($queryColumn);

                break;
            }
        }
    }

    /**
     * Set query builder.
     *
     * @param  mixed  $queryBuilder
     */
    public function setQueryBuilder($queryBuilder = null): void
    {
        $this->queryBuilder = $queryBuilder ?: $this->prepareQueryBuilder();
    }

    /**
     * Validated request.
     */
    public function validatedRequest(): array
    {
        request()->validate([
            'filters'         => ['sometimes', 'required', 'array'],
            'sort'            => ['sometimes', 'required', 'array'],
            'pagination'      => ['sometimes', 'required', 'array'],
            'export'          => ['sometimes', 'required', 'boolean'],
            'format'          => ['sometimes', 'required', 'in:csv,xls,xlsx'],
            'productIds'      => ['sometimes', 'array'],
            'mass_action_ids' => ['sometimes', 'required', 'boolean'],
        ]);

        return request()->only(['filters', 'sort', 'pagination', 'export', 'format', 'productIds', 'mass_action_ids']);
    }

    /**
     * Process all requested filters.
     *
     * @return Builder
     */
    public function processRequestedFilters(array $requestedFilters)
    {
        foreach ($requestedFilters as $requestedColumn => $requestedValues) {
            if ($requestedColumn === 'all') {
                $this->queryBuilder->where(function ($scopeQueryBuilder) use ($requestedValues): void {
                    foreach ($requestedValues as $value) {
                        collect($this->columns)
                            ->filter(fn ($column): bool => $column->searchable && $column->type !== ColumnTypeEnum::BOOLEAN->value)
                            ->each(fn ($column) => $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%'));
                    }
                });
            } else {
                $column = collect($this->columns)->first(fn ($c): bool => $c->index === $requestedColumn);

                match ($column->type) {
                    ColumnTypeEnum::STRING->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
                        }
                    }),
                    ColumnTypeEnum::INTEGER->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                        }
                    }),
                    ColumnTypeEnum::DROPDOWN->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), $value);
                        }
                    }),
                    ColumnTypeEnum::DATE_RANGE->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [
                                ($value[0] ?? '').' 00:00:01',
                                ($value[1] ?? '').' 23:59:59',
                            ]);
                        }
                    }),
                    ColumnTypeEnum::DATE_TIME_RANGE->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->whereBetween($column->getDatabaseColumnName(), [$value[0] ?? '', $value[1] ?? '']);
                        }
                    }),
                    ColumnTypeEnum::BOOLEAN->value => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.DB::rawQueryGrammar()->getBooleanValue($value).'%');
                        }
                    }),
                    default => $this->queryBuilder->where(function ($scopeQueryBuilder) use ($column, $requestedValues): void {
                        foreach ($requestedValues as $value) {
                            $scopeQueryBuilder->orWhere($column->getDatabaseColumnName(), 'LIKE', '%'.$value.'%');
                        }
                    }),
                };
            }
        }

        return $this->queryBuilder;
    }

    /**
     * Process requested sorting.
     *
     * @return Builder
     */
    public function processRequestedSorting(array $requestedSort)
    {
        if (! $this->sortColumn) {
            $this->sortColumn = $this->primaryColumn;
        }

        return $this->queryBuilder->orderBy($requestedSort['column'] ?? $this->sortColumn, $requestedSort['order'] ?? $this->sortOrder);
    }

    /**
     * Process requested pagination.
     */
    public function processRequestedPagination(array $requestedPagination): LengthAwarePaginator
    {
        return $this->queryBuilder->paginate(
            $requestedPagination['per_page'] ?? $this->itemsPerPage,
            ['*'],
            'page',
            $requestedPagination['page'] ?? 1
        );
    }

    /**
     * Process request.
     */
    public function processRequest(): void
    {
        /**
         * Store all request parameters in this variable; avoid using direct request helpers afterward.
         */
        $requestedParams = $this->validatedRequest();

        if (! empty($requestedParams['productIds'])) {
            $this->queryBuilder->whereIn('products.id', $requestedParams['productIds']);
        } else {
            $this->queryBuilder = $this->processRequestedFilters($requestedParams['filters'] ?? []);
            $this->queryBuilder = $this->processRequestedSorting($requestedParams['sort'] ?? []);
        }

        /**
         * The `export` parameter is validated as a boolean in the `validatedRequest`. An `empty` function will not work,
         * as it will always be treated as true because of "0" and "1".
         */
        if (isset($requestedParams['export']) && (bool) $requestedParams['export']) {
            $this->exportable = true;

            $gridData = $this instanceof ExportableInterface ? $this->getExportableData($requestedParams) : $this->queryBuilder->get();

            $this->setExportFile($gridData, $requestedParams['format']);

            return;
        }

        /**
         * A "select all matching" request only needs the primary-key values of the whole
         * filtered set, not the paginated, formatted rows. `pluck` on the builder would drop
         * the grid's aliased primary column (e.g. `products.id as product_id`), so resolve the
         * ids from the executed result set instead.
         */
        if (isset($requestedParams['mass_action_ids']) && (bool) $requestedParams['mass_action_ids']) {
            $this->massActionIds = $this->queryBuilder
                ->limit(self::MASS_ACTION_ID_LIMIT)
                ->get()
                ->pluck($this->primaryColumn)
                ->all();

            return;
        }

        $this->paginator = $this->processRequestedPagination($requestedParams['pagination'] ?? []);
    }

    /**
     * Set export file.
     *
     * @param  Collection  $records
     * @param  string  $format
     */
    public function setExportFile($records, $format = 'csv'): void
    {
        $this->exportFile = Excel::download(new DataGridExport($records), $this->getExportFileName().'.'.$format);
    }

    /**
     * Download export file.
     *
     * @return BinaryFileResponse
     */
    public function downloadExportFile()
    {
        return $this->exportFile;
    }

    /**
     * Format data.
     */
    public function formatData(): array
    {
        $paginator = $this->paginator->toArray();

        /**
         * TODO: need to handle this...
         */
        foreach ($this->columns as $column) {
            $column->input_type = $column->getFormInputType();

            $column->options = $column->getFormOptions();
        }

        foreach ($paginator['data'] as $record) {
            $record = $this->sanitizeRow($record);

            foreach ($this->columns as $column) {
                if ($closure = $column->closure) {
                    $record->{$column->index} = $closure($record);

                    $record->is_closure = true;
                }
            }

            $record->actions = [];

            foreach ($this->actions as $index => $action) {
                if (is_callable($action->condition) && ! ($action->condition)($record)) {
                    continue;
                }

                $getUrl = $action->url;

                $record->actions[] = [
                    'index'         => empty($action->index) ? 'action_'.$index + 1 : $action->index,
                    'icon'          => $action->icon,
                    'title'         => $action->title,
                    'method'        => $action->method,
                    'url'           => $getUrl($record),
                    'frontend_view' => $action?->frontendView,
                ];
            }
        }

        return [
            'id'                 => Crypt::encryptString(static::class),
            'columns'            => $this->columns,
            'actions'            => $this->actions,
            'mass_actions'       => $this->massActions,
            'search_placeholder' => __($this->searchPlaceholder),
            'records'            => $paginator['data'],
            'meta'               => [
                'primary_column'     => $this->primaryColumn,
                'select_all_enabled' => $this->enableSelectAll,
                'from'               => $paginator['from'],
                'to'                 => $paginator['to'],
                'total'              => $paginator['total'],
                'per_page_options'   => [10, 20, 30, 40, 50],
                'per_page'           => $paginator['per_page'],
                'current_page'       => $paginator['current_page'],
                'last_page'          => $paginator['last_page'],
            ],
        ];
    }

    /**
     * Prepare all the setup for datagrid.
     */
    public function prepare(): void
    {
        $this->prepareColumns();

        $this->prepareActions();

        $this->prepareMassActions();

        $this->setQueryBuilder();

        $this->processRequest();
    }

    /**
     * Prepare all the setup for datagrid.
     */
    public function sanitizeRow($row): \stdClass
    {
        /**
         * Convert stdClass to array.
         */
        $tempRow = json_decode(json_encode($row), true);

        foreach ($tempRow as $column => $value) {
            if (! is_string($tempRow[$column])) {
                continue;
            }

            if (is_array($value)) {
                return $this->sanitizeRow($tempRow[$column]);
            }
            $row->{$column} = strip_tags((string) $value);
        }

        return $row;
    }

    /**
     * To json.
     */
    public function toJson()
    {
        $this->prepare();

        if ($this->exportable) {
            return $this->downloadExportFile();
        }

        if (! is_null($this->massActionIds)) {
            return response()->json(['ids' => $this->massActionIds]);
        }

        return response()->json($this->formatData());
    }

    /**
     * Set File name to be used during quick export
     */
    protected function getExportFileName(): string
    {
        return Str::random(36);
    }
}
