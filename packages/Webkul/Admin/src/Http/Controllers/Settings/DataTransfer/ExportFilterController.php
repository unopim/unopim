<?php

namespace Webkul\Admin\Http\Controllers\Settings\DataTransfer;

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

/**
 * Serves the dynamic option lists consumed by the export profile's scope
 * filters (Channel, Locale, Currency). Responses follow the
 * { options, page, lastPage } contract expected by the async multiselect
 * handler (v-async-select-handler).
 */
class ExportFilterController extends Controller
{
    /**
     * Number of options returned per page.
     */
    const PER_PAGE = 20;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected ChannelRepository $channelRepository,
        protected CurrencyRepository $currencyRepository,
        protected LocaleRepository $localeRepository,
        protected AttributeRepository $attributeRepository,
        protected AttributeFamilyRepository $attributeFamilyRepository,
        protected CategoryRepository $categoryRepository,
    ) {}

    /**
     * Ensures the current admin may run product exports before serving any
     * filter option list.
     */
    protected function ensureExportPermission(): void
    {
        if (! bouncer()->hasPermission('data_transfer.export')) {
            abort(403, 'This action is unauthorized');
        }
    }

    /**
     * Dynamic list of channels available as an export scope.
     */
    public function channels(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->channelRepository->all()->map(fn ($channel) => [
            'code'  => $channel->code,
            'label' => $channel->name ?: $channel->code,
        ])->values();

        return $this->respondWithOptions($options);
    }

    /**
     * Dynamic list of locales, scoped to the selected channel(s) when supplied
     * and falling back to every active locale otherwise.
     */
    public function locales(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->scopedRecords('locales', fn () => $this->localeRepository->getActiveLocales())
            ->map(fn ($locale) => [
                'code'  => $locale->code,
                'label' => $locale->name ?: $locale->code,
            ])
            ->values();

        return $this->respondWithOptions($options);
    }

    /**
     * Dynamic list of currencies, scoped to the selected channel(s) when
     * supplied and falling back to every active currency otherwise.
     */
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

    public function attributes(): JsonResponse
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
                'page'     => 1,
                'lastPage' => 1,
            ]);
        }

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->whereTranslationLike('name', '%'.$search.'%')
                    ->orWhere('code', 'LIKE', '%'.$search.'%');
            });
        }

        $page = max(1, (int) request('page', 1));

        $paginator = $query->orderBy('id')->paginate(self::PER_PAGE, ['*'], 'page', $page);

        return new JsonResponse([
            'options'  => $this->mapAttributes(collect($paginator->items())),
            'page'     => $paginator->currentPage(),
            'lastPage' => max(1, $paginator->lastPage()),
        ]);
    }

    /**
     * Dynamic list of attribute families available as a product filter.
     */
    public function attributeFamilies(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->attributeFamilyRepository->with('translations')->all()->map(fn ($family) => [
            'code'  => $family->code,
            'label' => $family->name ?: $family->code,
        ])->values();

        return $this->respondWithOptions($options);
    }

    /**
     * Dynamic list of categories available as a product filter.
     */
    public function categories(): JsonResponse
    {
        $this->ensureExportPermission();

        $options = $this->categoryRepository->all()->map(fn ($category) => [
            'code'  => $category->code,
            'label' => $category->name ?: $category->code,
        ])->values();

        return $this->respondWithOptions($options);
    }

    /**
     * Maps attribute models into the option shape consumed by the async
     * multiselect handler.
     */
    protected function mapAttributes(Collection $attributes): array
    {
        return $attributes->map(fn ($attribute) => [
            'id'    => $attribute->id,
            'code'  => $attribute->code,
            'label' => $attribute->name ?: $attribute->code,
            'type'  => $attribute->type,
        ])->values()->all();
    }

    /**
     * Attribute codes to exclude from the list, supplied as a comma separated
     * string or array via the "exclude" query param. Empty when none requested.
     */
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

    /**
     * Resolves the channel-scoped records for the given relation, or the
     * fallback set when no channel is selected.
     */
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
     * Applies rehydration (edit screen), search and pagination to the option
     * set and returns it in the shape the async multiselect handler expects.
     */
    protected function respondWithOptions(Collection $options): JsonResponse
    {
        $identifiers = request('identifiers');

        if (! empty($identifiers['values'])) {
            $values = is_array($identifiers['values'])
                ? $identifiers['values']
                : explode(',', (string) $identifiers['values']);

            return new JsonResponse([
                'options'  => $options->whereIn('code', $values)->values(),
                'page'     => 1,
                'lastPage' => 1,
            ]);
        }

        $search = trim((string) request('query', ''));

        if ($search !== '') {
            $options = $options->filter(
                fn ($option) => stripos($option['code'], $search) !== false
                    || stripos($option['label'], $search) !== false
            )->values();
        }

        $page = max(1, (int) request('page', 1));
        $lastPage = max(1, (int) ceil($options->count() / self::PER_PAGE));

        return new JsonResponse([
            'options'  => $options->forPage($page, self::PER_PAGE)->values(),
            'page'     => $page,
            'lastPage' => $lastPage,
        ]);
    }
}
