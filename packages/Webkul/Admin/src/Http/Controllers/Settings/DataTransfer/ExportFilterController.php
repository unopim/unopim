<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Enums\ProductExportScope;
use Webkul\DataTransfer\Helpers\Formatters\ScopeFilterValue;

class ExportFilterController extends Controller
{
    const PER_PAGE = 20;

    const DEFAULT_PAGE = 1;

    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    protected function ensureExportPermission(): void
    {
        if (! bouncer()->hasPermission('data_transfer.export')) {
            abort(403, trans('admin::app.common.unauthorized'));
        }
    }

    public function channels(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->channelRepository->all()->map(fn ($channel) => [
            'code'  => $channel->code,
            'label' => $channel->name ?? $channel->code,
        ])->values();

        return $this->respondWithOptions($options);
    }

    public function locales(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->scopedRecords('locales', fn () => $this->localeRepository->getActiveLocales())
            ->map(fn ($locale) => [
                'code'  => $locale->code,
                'label' => $locale->name ?? $locale->code,
            ])
            ->values();

        return $this->respondWithOptions($options);
    }

    public function currencies(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->scopedRecords('currencies', fn () => $this->currencyRepository->getActiveCurrencies())
            ->map(fn ($currency) => [
                'code'  => $currency->code,
                'label' => $currency->name ? $currency->code.' - '.$currency->name : $currency->code,
            ])
            ->values();

        return $this->respondWithOptions($options);
    }

    public function getAttributes(): JsonResponse
    {
        $this->ensureExportPermission();

        $query = $this->attributeRepository->getModel()->newQuery()->with('translations');

        $exclude = $this->excludedCodes();

        if (! empty($exclude)) {
            $query->whereNotIn('code', $exclude);
        }

        $identifiers = request('identifiers');

        if (! empty($identifiers['values'])) {
            $values = is_array($identifiers['values'])
                ? $identifiers['values']
                : explode(',', (string) $identifiers['values']);

            return new JsonResponse([
                'options'  => $this->mapAttributes($query->whereIn('code', $values)->get()),
                'page'     => self::DEFAULT_PAGE,
                'lastPage' => self::DEFAULT_PAGE,
            ]);
        }

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereTranslationLike('name', '%'.$search.'%')
                    ->orWhere('code', 'LIKE', '%'.$search.'%');
            });
        }

        $page = max(self::DEFAULT_PAGE, (int) request('page', self::DEFAULT_PAGE));

        $paginator = $query->orderBy('id')->paginate(self::PER_PAGE, ['*'], 'page', $page);

        return new JsonResponse([
            'options'  => $this->mapAttributes(collect($paginator->items())),
            'page'     => $paginator->currentPage(),
            'lastPage' => max(self::DEFAULT_PAGE, $paginator->lastPage()),
        ]);
    }

    public function attributeFamilies(): JsonResponse
    {
        $this->ensureExportPermission();

        return $this->respondWithPaginatedOptions(
            $this->attributeFamilyRepository->getModel()->newQuery()->with('translations')->orderBy('id'),
            fn ($family) => [
                'code'  => $family->code,
                'label' => $family->name ?? $family->code,
            ],
            fn (Builder $query, string $search) => $query->where(
                fn ($builder) => $builder->whereTranslationLike('name', '%'.$search.'%')
                    ->orWhere('code', 'LIKE', '%'.$search.'%')
            ),
        );
    }

    public function categories(): JsonResponse
    {
        $this->ensureExportPermission();

        $locale = core()->getRequestedLocaleCode();

        return $this->respondWithPaginatedOptions(
            $this->categoryRepository->getModel()->newQuery()->orderBy('id'),
            fn ($category) => [
                'code'  => $category->code,
                'label' => $category->additional_data['locale_specific'][$locale]['name'] ?? '['.$category->code.']',
            ],
            fn (Builder $query, string $search) => $query->where(
                fn ($builder) => $builder->where('additional_data->locale_specific->'.$locale.'->name', 'LIKE', '%'.$search.'%')
                    ->orWhere('code', 'LIKE', '%'.$search.'%')
            ),
        );
    }

    protected function mapAttributes(Collection $attributes): array
    {
        return $attributes->map(fn ($attribute) => [
            'id'    => $attribute->id,
            'code'  => $attribute->code,
            'label' => $attribute->name ?? $attribute->code,
            'type'  => $attribute->type,
        ])->values()->all();
    }

    protected function excludedCodes(): array
    {
        $exclude = request('exclude', []);

        if (is_string($exclude)) {
            $exclude = explode(',', $exclude);
        }

        return array_values(array_filter(array_map(
            fn ($code) => trim((string) $code),
            (array) $exclude
        )));
    }

    protected function scopedRecords(string $relation, callable $fallback): Collection
    {
        $channelCodes = ScopeFilterValue::toCodes(request(ProductExportScope::CHANNELS->value));

        if (empty($channelCodes)) {
            return $fallback();
        }

        return $this->channelRepository
            ->with([$relation])
            ->findWhereIn('code', $channelCodes)
            ->flatMap(fn ($channel) => $channel->{$relation})
            ->unique('code')
            ->sortBy('code')
            ->values();
    }

    /**
     * Same option contract as {@see respondWithOptions()}, but selection, search
     * and paging all happen in SQL. Catalogue-sized tables — categories and
     * attribute families run to six figures — must never be hydrated in full
     * just to hand back a page of twenty options.
     *
     * @param  callable(Model): array{code: string, label: string}  $mapper
     * @param  callable(Builder, string): mixed  $search
     */
    protected function respondWithPaginatedOptions(Builder $query, callable $mapper, callable $search): JsonResponse
    {
        $identifiers = request('identifiers');

        if (! empty($identifiers['values'])) {
            $values = is_array($identifiers['values'])
                ? $identifiers['values']
                : explode(',', (string) $identifiers['values']);

            return new JsonResponse([
                'options'  => $query->whereIn('code', $values)->get()->map($mapper)->values(),
                'page'     => self::DEFAULT_PAGE,
                'lastPage' => self::DEFAULT_PAGE,
            ]);
        }

        if (($term = trim((string) request('query', ''))) !== '') {
            $search($query, $term);
        }

        $page = max(self::DEFAULT_PAGE, (int) request('page', self::DEFAULT_PAGE));

        $paginator = $query->paginate(self::PER_PAGE, ['*'], 'page', $page);

        return new JsonResponse([
            'options'  => collect($paginator->items())->map($mapper)->values(),
            'page'     => $paginator->currentPage(),
            'lastPage' => max(self::DEFAULT_PAGE, $paginator->lastPage()),
        ]);
    }

    protected function respondWithOptions(Collection $options): JsonResponse
    {
        $identifiers = request('identifiers');

        if (! empty($identifiers['values'])) {
            $values = is_array($identifiers['values'])
                ? $identifiers['values']
                : explode(',', (string) $identifiers['values']);

            return new JsonResponse([
                'options'  => $options->whereIn('code', $values)->values(),
                'page'     => self::DEFAULT_PAGE,
                'lastPage' => self::DEFAULT_PAGE,
            ]);
        }

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $options = $options->filter(
                fn ($option) => stripos($option['code'], $search) !== false
                    || stripos($option['label'], $search) !== false
            )->values();
        }

        $page = max(self::DEFAULT_PAGE, (int) request('page', self::DEFAULT_PAGE));
        $lastPage = max(self::DEFAULT_PAGE, (int) ceil($options->count() / self::PER_PAGE));

        return new JsonResponse([
            'options'  => $options->forPage($page, self::PER_PAGE)->values(),
            'page'     => $page,
            'lastPage' => $lastPage,
        ]);
    }
}
