<?php

namespace Webkul\Category\Database\Eloquent;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Kalnoy\Nestedset\QueryBuilder as BaseBuilder;

/**
 * @mixin \Illuminate\Database\Query\Builder
 */
class Builder extends BaseBuilder
{
    /**
     * The model being queried. Redeclared to correct the parent's docblock,
     * which references the NodeTrait trait as if it were a class.
     *
     * @var Model
     */
    protected $model;

    /**
     * Paginate the given query.
     *
     * @param  int  $perPage
     * @param  array  $columns
     * @param  string  $pageName
     * @param  int|null  $page
     * @return LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null, $total = null)
    {
        $page = $page ?: Paginator::resolveCurrentPage($pageName);

        $perPage = $perPage ?: $this->model->getPerPage();

        $total = $total ?: $this->toBase()->getCountForPagination($columns);

        $results = $total
            ? $this->forPage($page, $perPage)->get($columns)
            : $this->model->newCollection();

        return $this->paginator($results, $total, $perPage, $page, [
            'path'     => Paginator::resolveCurrentPath(),
            'pageName' => $pageName,
        ]);
    }
}
