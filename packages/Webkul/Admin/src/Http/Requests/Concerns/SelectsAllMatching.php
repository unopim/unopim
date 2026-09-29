<?php

namespace Webkul\Admin\Http\Requests\Concerns;

use Illuminate\Support\LazyCollection;
use Illuminate\Validation\Rule;
use Webkul\DataGrid\DataGrid;

/**
 * Lets a mass-action request target every record matching the grid's filters (`select_all`)
 * instead of an explicit `indices` list, so the selection is not bounded by a client-side id list.
 */
trait SelectsAllMatching
{
    /**
     * Whether the request targets every record matching the grid's filters.
     */
    public function selectsAllMatching(): bool
    {
        return $this->boolean('select_all');
    }

    /**
     * Resolve the selected ids, streaming them from the grid when every matching record is selected.
     *
     * @param  class-string<DataGrid>  $dataGrid
     * @return LazyCollection<int, mixed>
     */
    public function selectedIds(string $dataGrid): LazyCollection
    {
        if ($this->selectsAllMatching()) {
            return app($dataGrid)->getMatchingIds();
        }

        return LazyCollection::make($this->input('indices', []));
    }

    /**
     * Grid parameters that reproduce the selection outside this request, e.g. on the queue.
     *
     * @return array<string, mixed>
     */
    public function selectionParams(): array
    {
        return $this->only(['filters', 'channel', 'locale', 'managedColumns']);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function selectionRules(): array
    {
        return [
            'select_all' => ['sometimes', 'boolean'],
            'indices'    => [Rule::requiredIf(fn (): bool => ! $this->selectsAllMatching()), 'array'],
            'indices.*'  => ['integer'],
            'filters'    => ['sometimes', 'array'],
            'sort'       => ['sometimes', 'required', 'array'],
        ];
    }
}
