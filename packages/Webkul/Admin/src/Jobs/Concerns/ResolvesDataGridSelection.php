<?php

namespace Webkul\Admin\Jobs\Concerns;

use Illuminate\Http\Request;
use Webkul\DataGrid\DataGrid;

/**
 * Rebuilds a datagrid selection outside the request that made it, e.g. on the queue.
 *
 * Expects `$dataGrid` (class-string of the grid) and `$gridParams` (its filters and scope) on the using class.
 */
trait ResolvesDataGridSelection
{
    /**
     * Run the callback with the grid's parameters bound as the current request, restoring the previous one afterwards.
     *
     * @template TReturn
     *
     * @param  callable(DataGrid): TReturn  $callback
     * @return TReturn
     */
    protected function withGridRequest(callable $callback): mixed
    {
        $previousRequest = app('request');

        app()->instance('request', Request::create('/', 'GET', $this->gridParams));

        try {
            return $callback(app($this->dataGrid));
        } finally {
            app()->instance('request', $previousRequest);
        }
    }
}
