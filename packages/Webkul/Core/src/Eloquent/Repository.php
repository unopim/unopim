<?php

namespace Webkul\Core\Eloquent;

use Prettus\Repository\Contracts\CacheableInterface;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;

abstract class Repository extends BaseRepository implements CacheableInterface
{
    public mixed $cleanEnabled = null;

    public mixed $cacheOnly = null;

    public mixed $cacheExcept = null;

    use CacheableRepository;

    protected bool $cacheEnabled = false;

    public function allowedClean(): bool
    {
        if ($this->cleanEnabled === null) {
            return config('repository.cache.clean.enabled', true);
        }

        return $this->cleanEnabled;
    }

    protected function allowedCache(mixed $method): bool
    {
        $className = static::class;

        $cacheEnabled = config("repository.cache.repositories.{$className}.enabled", config('repository.cache.enabled', true));

        if (! $cacheEnabled) {
            return false;
        }

        $cacheOnly = $this->cacheOnly ?? config("repository.cache.repositories.{$className}.allowed.only", config('repository.cache.allowed.only'));
        $cacheExcept = $this->cacheExcept ?? config("repository.cache.repositories.{$className}.allowed.except", config('repository.cache.allowed.only'));

        if (is_array($cacheOnly)) {
            return in_array($method, $cacheOnly);
        }

        if (is_array($cacheExcept)) {
            return ! in_array($method, $cacheExcept);
        }

        return is_null($cacheOnly) && is_null($cacheExcept);
    }

    /**
     * @throws RepositoryException
     */
    #[\Override]
    public function resetModel(): static
    {
        $this->makeModel();

        return $this;
    }

    /**
     * Find data by field and value
     *
     * @param  string  $value
     */
    public function findOneByField(string $field, mixed $value = null, array $columns = ['*']): mixed
    {
        $model = $this->findByField($field, $value, $columns = ['*']);

        return $model->first();
    }

    /**
     * Find data by field and value
     *
     * @param  string  $field
     * @param  string  $value
     */
    public function findOneWhere(array $where, array $columns = ['*']): mixed
    {
        $model = $this->findWhere($where, $columns);

        return $model->first();
    }

    /**
     * Find data by id
     *
     * @param  int  $id
     * @param  array  $columns
     */
    #[\Override]
    public function find($id, $columns = ['*']): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->find($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * Find data by id
     *
     * @param  int  $id
     */
    public function findOrFail(mixed $id, array $columns = ['*']): mixed
    {
        $this->applyCriteria();
        $this->applyScope();
        $model = $this->model->findOrFail($id, $columns);
        $this->resetModel();

        return $this->parserResult($model);
    }

    /**
     * Count results of repository
     *
     * @param  string  $columns
     */
    #[\Override]
    public function count(array $where = [], $columns = '*'): int
    {
        $this->applyCriteria();
        $this->applyScope();

        if ($where !== []) {
            $this->applyConditions($where);
        }

        $result = $this->model->count($columns);
        $this->resetModel();
        $this->resetScope();

        return $result;
    }

    /**
     * @param  string  $columns
     */
    public function sum(mixed $columns): mixed
    {
        $this->applyCriteria();
        $this->applyScope();

        $sum = $this->model->sum($columns);
        $this->resetModel();

        return $sum;
    }

    /**
     * @param  string  $columns
     */
    public function avg(mixed $columns): mixed
    {
        $this->applyCriteria();
        $this->applyScope();

        $avg = $this->model->avg($columns);
        $this->resetModel();

        return $avg;
    }

    #[\Override]
    public function getModel(): mixed
    {
        return $this->model;
    }
}
