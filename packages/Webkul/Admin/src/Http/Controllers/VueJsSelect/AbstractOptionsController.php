<?php

namespace Webkul\Admin\Http\Controllers\VueJsSelect;

use Illuminate\Database\Eloquent\Model;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Core\Eloquent\TranslatableModel;

class AbstractOptionsController extends Controller
{
    const DEFAULT_PER_PAGE = 20;

    /**
     * Return instance of Controller
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
    ) {}

    protected function getEntityRepository($entityName)
    {
        return match ($entityName) {
            'attributes'         => $this->attributeRepository,
            default              => throw new \Exception('Not implemented for '.$entityName)
        };

    }

    /**
     * Fetch options according to parameters for search, page and id
     */
    protected function getOptionsByParams(
        string $entityName,
        int|string $page,
        string $query = '',
        ?array $queryParams = [],
        int $limit = self::DEFAULT_PER_PAGE,
        bool $isPaginate = true
    ) {
        $repository = $this->getEntityRepository($entityName);

        if (isset($queryParams['filters']) && is_array($queryParams['filters'])) {
            $repository = $this->applyFilters($repository, $queryParams['filters']);
        }

        if (! empty($query)) {
            $repository = $this->applySearchQuery($repository, $query, $entityName);
        }

        $initializeValues = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($initializeValues)) {
            $repository = $this->applyInitialValues($repository, $initializeValues);
        }

        if ($isPaginate) {
            return $repository->orderBy('id')->paginate($limit, ['*'], 'paginate', $page);
        } else {
            return $repository->orderBy('id')->get();
        }
    }

    /**
     * Get translated label for the entity
     */
    protected function getTranslatedLabel(string $currentLocaleCode, TranslatableModel $option): string
    {
        $translation = $option->translate($currentLocaleCode);

        return $translation?->label ?? $translation?->name;
    }

    /**
     * format option for select component
     */
    protected function formatOption(Model $option, string $currentLocaleCode)
    {
        $translatedOptionLabel = $this->getTranslatedLabel($currentLocaleCode, $option);

        return [
            'id'    => $option->id,
            'code'  => $option->code,
            'label' => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
            ...$option->makeHidden(['translations'])->toArray(),
        ];
    }

    /**
     * Apply Filters according to query on the query builder object
     */
    protected function applyFilters($repository, array $filters)
    {
        foreach ($filters as $filter) {
            $column = $filter['column'] ?? null;
            $operator = $filter['operator'] ?? '=';
            $value = $filter['value'] ?? null;

            if ($column && isset($value)) {
                $repository = $filter['operator'] === 'IN' ? $repository->orWhereIn($column, $value) : $repository->where($column, $operator, $value);
            }
        }

        return $repository;
    }

    /**
     * Applies search query for the select field
     */
    protected function applySearchQuery($repository, string $query, string $entityName)
    {
        return $repository->where(function ($queryBuilder) use ($query, $entityName) {
            $queryBuilder->whereTranslationLike($this->getTranslationColumnName($entityName), '%'.$query.'%')
                ->orWhere('code', $query);
        });
    }

    protected function applyInitialValues($repository, array $initializeValues)
    {
        return $repository->whereIn(
            $initializeValues['columnName'],
            is_array($initializeValues['values']) ? $initializeValues['values'] : [$initializeValues['values']]
        );
    }

    /**
     * Translation for the models label to be used for search
     */
    protected function getTranslationColumnName(string $entityName): string
    {
        return match ($entityName) {
            'category_fields' => 'name',
            'channel'         => 'name',
            'attributes'      => 'name',
            default           => 'label'
        };
    }
}
