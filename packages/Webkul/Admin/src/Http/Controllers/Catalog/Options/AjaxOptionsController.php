<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Options;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldOptionRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Eloquent\TranslatableModel;

class AjaxOptionsController extends Controller
{
    const DEFAULT_PER_PAGE = 20;

    /**
     * Upper bound for a client-requested page size, used by "select all" style
     * actions that need every matching record in a single request.
     */
    const MAX_PER_PAGE = 5000;

    /**
     * This is used for fetching attribute options for a specific attribute id
     */
    const ENTITY_ATTRIBUTE_OPTION = 'attribute';

    /**
     * This is used for fetching category field options for a specific category field
     */
    const ENTITY_CATEGORY_FIELD_OPTION = 'category_field';

    /**
     * This is used for fetching attribute families
     */
    const ENTITY_ATTRIBUTE_FAMILY = 'attribute_family';

    /**
     * This is used for fetching attribute groups
     */
    const ENTITY_ATTRIBUTE_GROUP = 'attribute_group';

    /**
     * This is used for fetching attributes
     */
    const ENTITY_ATTRIBUTE = 'attributes';

    /**
     * This is used for fetching categories
     */
    const ENTITY_CATEGORY = 'category';

    /**
     * Return instance of Controller
     */
    public function __construct(
        protected CategoryFieldOptionRepository $categoryFieldOptionsRepository,
        protected AttributeOptionRepository $attributeOptionsRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeRepository $attributeRepository,
        protected CategoryRepository $categoryRepository
    ) {}

    /**
     * Fetch and format options for async select and multiselect handlers
     */
    public function getOptions(): JsonResponse
    {
        $attributeId = request()->input('attributeId') ?? request()->input('attribute_id');
        $entityName = request()->input('entityName') ?? request()->input('entity_name');
        $page = max(1, (int) request()->input('page', 1));
        $query = request()->input('query') ?? request()->input('search') ?? '';

        $perPage = $this->resolvePerPage(request()->input('perPage'));

        $queryParams = request()->except(['page', 'perPage', 'query', 'search', 'entityName', 'entity_name', 'attributeId', 'attribute_id']);

        if (! $entityName) {
            return new JsonResponse(['options' => [], 'page' => 1, 'lastPage' => 1]);
        }

        $options = $this->getOptionsByParams($attributeId, $entityName, $page, $query, $queryParams, $perPage);

        $currentLocaleCode = core()->getRequestedLocaleCode();

        $formattedOptions = [];

        foreach ($options as $option) {
            $translatedOptionLabel = $entityName === self::ENTITY_CATEGORY
                ? $option->name
                : $this->getTranslatedLabel($currentLocaleCode, $option, $entityName);

            $formattedOptions[] = [
                'id'    => $option->id,
                'code'  => $option->code,
                'label' => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
                ...$option->makeHidden(['translations', 'label'])->toArray(),
            ];
        }

        return new JsonResponse([
            'options'  => $formattedOptions,
            'page'     => $options->currentPage(),
            'lastPage' => $options->lastPage(),
            'total'    => $options->total(),
        ]);
    }

    /**
     * Fetch options according to parameters for search, page and id
     */
    protected function getOptionsByParams(
        int|string|null $id,
        string $entityName,
        int $page,
        string $query = '',
        ?array $queryParams = [],
        int $perPage = self::DEFAULT_PER_PAGE
    ): LengthAwarePaginator {
        $isCategory = $entityName === self::ENTITY_CATEGORY;

        $repository = $isCategory
            ? $this->categoryQuery($query)
            : $this->getRepository($entityName);

        if (! $isCategory) {
            // Labels are resolved per row via translate()/toArray(); eager load the
            // translations up front so formatting the page is a single query, not N+1.
            $repository = $repository->with(['translations']);
        }

        if ($id && ! $isCategory) {
            $repository = $repository->where($entityName.'_id', $id);
        }

        if (! empty($query) && ! $isCategory) {
            $repository = $repository->where(function ($queryBuilder) use ($query, $entityName) {
                $queryBuilder->whereTranslationLike($this->getTranslationColumnName($entityName), '%'.$query.'%')
                    ->orWhere('code', $query);
            });
        }

        $searchIdentifiers = isset($queryParams['identifiers']['columnName']) ? $queryParams['identifiers'] : [];

        if (! empty($searchIdentifiers) && isset($searchIdentifiers['columnName']) && isset($searchIdentifiers['values'])) {
            $repository = $repository->whereIn(
                $searchIdentifiers['columnName'],
                is_array($searchIdentifiers['values']) ? $searchIdentifiers['values'] : [$searchIdentifiers['values']]
            );
        }

        if (isset($queryParams['exclude']) && is_array($queryParams['exclude']) && isset($queryParams['exclude']['values'])) {
            $repository = $repository->whereNotIn($queryParams['exclude']['columnName'], $queryParams['exclude']['values']);
        }

        if ($isCategory) {
            return $repository->defaultOrder()->paginate($perPage, ['*'], 'paginate', $page);
        }

        return $repository->orderBy($this->getSortColumn($entityName))->paginate($perPage, ['*'], 'paginate', $page);
    }

    /**
     * Category labels live in an `additional_data` JSON column, not a translations table.
     */
    protected function categoryQuery(string $query): Builder
    {
        $locale = core()->getRequestedLocaleCode();

        $builder = $this->categoryRepository->getModel()->newQuery();

        if ($query !== '') {
            $builder->where(function ($builder) use ($query, $locale) {
                $builder->where('additional_data->locale_specific->'.$locale.'->name', 'LIKE', '%'.$query.'%')
                    ->orWhere('code', 'LIKE', '%'.$query.'%');
            });
        }

        return $builder;
    }

    /**
     * Clamp a client-supplied page size into a safe range, falling back to the
     * default when the value is absent or invalid.
     */
    protected function resolvePerPage(mixed $perPage): int
    {
        $perPage = (int) $perPage;

        if ($perPage < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min($perPage, self::MAX_PER_PAGE);
    }

    /**
     * TODO: Add attribute, family, attribute group, category, products support here
     * Get Repository according to entity name
     */
    protected function getRepository(string $entityName): Repository
    {
        return match ($entityName) {
            self::ENTITY_ATTRIBUTE_OPTION      => $this->attributeOptionsRepository,
            self::ENTITY_CATEGORY_FIELD_OPTION => $this->categoryFieldOptionsRepository,
            self::ENTITY_ATTRIBUTE_FAMILY      => $this->attributeFamilyRepository,
            self::ENTITY_ATTRIBUTE_GROUP       => $this->attributeGroupRepository,
            self::ENTITY_ATTRIBUTE             => $this->attributeRepository,
            default                            => throw new \Exception('Not implemented for '.$entityName)
        };
    }

    /**
     * Translation for the models label to be used for search
     */
    protected function getTranslationColumnName(string $entityName): string
    {
        return match ($entityName) {
            self::ENTITY_ATTRIBUTE_FAMILY, self::ENTITY_ATTRIBUTE_GROUP, self::ENTITY_ATTRIBUTE => 'name',
            default                                                                             => 'label'
        };
    }

    /**
     * Get translated label for the entity, falling back to any available locale when
     * the requested locale translation is missing.
     */
    protected function getTranslatedLabel(string $currentLocaleCode, TranslatableModel $option, string $entityName): ?string
    {
        return $option->getTranslatedValueWithFallback(
            $this->getTranslationColumnName($entityName),
            $currentLocaleCode
        );
    }

    /**
     * Get the sort column based on the entity name
     */
    protected function getSortColumn(string $entityName): string
    {
        return match ($entityName) {
            self::ENTITY_ATTRIBUTE_OPTION, self::ENTITY_CATEGORY_FIELD_OPTION => 'sort_order',
            default                                                           => 'id',
        };
    }
}
