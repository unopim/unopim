<?php

namespace Webkul\Admin\Http\Controllers\Catalog\Options;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\Category\Repositories\CategoryFieldOptionRepository;
use Webkul\Core\Eloquent\Repository;

class AjaxOptionsController extends Controller
{
    const DEFAULT_PER_PAGE = 20;

    /**
     * Return instance of Controller
     */
    public function __construct(
        protected CategoryFieldOptionRepository $categoryFieldOptionsRepository,
        protected AttributeOptionRepository $attributeOptionsRepository
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

        $options = $this->getOptionsByParams($attributeId, $entityName, $page, $query);

        $currentLocaleCode = core()->getRequestedLocaleCode();

        $formattedoptions = [];

        foreach ($options as $option) {
            $translatedOptionLabel = $option->translate($currentLocaleCode)?->label;

            $formattedoptions[] = [
                'id'    => $option->id,
                'code'  => $option->code,
                'label' => ! empty($translatedOptionLabel) ? $translatedOptionLabel : "[{$option->code}]",
            ];
        }

        return new JsonResponse([
            'options'  => $formattedoptions,
            'page'     => $options->currentPage(),
            'lastPage' => $options->lastPage(),
        ]);
    }

    /**
     * Fetch options according to parameters for search, page and id
     */
    protected function getOptionsByParams(
        int|string $id,
        string $entityName,
        int|string $page,
        string $query = ''
    ): LengthAwarePaginator {
        $repository = $this->getRepository($entityName);

        $repository = $repository->where($entityName.'_id', $id);

        if (! empty($query)) {
            $repository = $repository->where(function ($queryBuilder) use ($query) {
                $queryBuilder->whereTranslationLike('label', '%'.$query.'%')
                    ->orWhere('code', $query);
            });
        }

        return $repository->orderBy('id')->paginate(self::DEFAULT_PER_PAGE, ['*'], 'page', $page);
    }

    /**
     * Get Repository according to entity name
     */
    private function getRepository(string $entityName): Repository
    {
        return match ($entityName) {
            'attribute'      => $this->attributeOptionsRepository,
            'category_field' => $this->categoryFieldOptionsRepository,
            default          => throw new \Excpetion('Not implemented for '.$entityName)
        };
    }
}
