<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Options;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldOptionRepository;
use Webkul\Core\Eloquent\Repository;
use Webkul\Core\Eloquent\TranslatableModel;

class AjaxOptionsController extends Controller
{
    const DEFAULT_PER_PAGE = 20;

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
     * Return instance of Controller
     */
    public function __construct(
        protected CategoryFieldOptionRepository $categoryFieldOptionsRepository,
        protected AttributeOptionRepository $attributeOptionsRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeRepository $attributeRepository
    ) {}

    /**
     * Fetch and format options for async select and multiselect handlers
     */
    public function getOptions()
    {
        $attributeId = request()->get('attributeId');
        $entityName = request()->get('entityName');
        $page = request()->get('page');
        $query = request()->get('query') ?? '';

        $queryParams = request()->except(['page', 'query', 'entityName', 'attributeId']);

        $options = $this->getOptionsByParams($attributeId, $entityName, $page, $query, $queryParams);

        $currentLocaleCode = core()->getRequestedLocaleCode();

        $formattedOptions = [];

        foreach ($options as $option) {
            $translatedOptionLabel = $this->getTranslatedLabel($currentLocaleCode, $option, $entityName);

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
        ]);
    }

    /**
     * Fetch options according to parameters for search, page and id
     */
    protected function getOptionsByParams(
        int|string|null $id,
        string $entityName,
        int|string $page,
        string $query = '',
        ?array $queryParams = []
    ): LengthAwarePaginator {
        $repository = $this->getRepository($entityName);

        if ($id) {
            $repository = $repository->where($entityName.'_id', $id);
        }

        if (! empty($query)) {
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

        return $repository->orderBy($this->getSortColumn($entityName))->paginate(self::DEFAULT_PER_PAGE, ['*'], 'paginate', $page);
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
            default => 'label'
        };
    }

    /**
     * Get translated label for the entity
     */
    protected function getTranslatedLabel(string $currentLocaleCode, TranslatableModel $option, string $entityName): ?string
    {
        $translationColumn = $this->getTranslationColumnName($entityName);

        $translation = $option->translate($currentLocaleCode);

        if (! empty($translation?->{$translationColumn})) {
            return $translation->{$translationColumn};
        }

        foreach ($option->translations as $localeTranslation) {
            if (! empty($localeTranslation->{$translationColumn})) {
                return $localeTranslation->{$translationColumn};
            }
        }

        return "[{$option->code}]";
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
