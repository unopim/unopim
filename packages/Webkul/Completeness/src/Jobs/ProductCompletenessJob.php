<?php

namespace Webkul\Completeness\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Contracts\VariantStructurePlanner;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

class ProductCompletenessJob implements ShouldQueue
{
    use Batchable, \Illuminate\Foundation\Queue\Queueable;

    protected ProductRepository $productRepository;

    protected ChannelRepository $channelRepository;

    protected LocaleRepository $localeRepository;

    protected AttributeRepository $attributeRepository;

    protected CompletenessSettingsRepository $completenessSettingsRepository;

    protected ProductCompletenessScoreRepository $completenessResultsRepository;

    protected VariantStructurePlanner $variantStructurePlanner;

    protected array $channels = [];

    protected array $completenessSettings = [];

    protected array $attributeCache = [];

    public $tries = 3;

    public function __construct(protected array $productIds)
    {
        $this->queue = 'system';
    }

    public function handle(): void
    {
        $this->resolveDependencies();
        $this->loadStaticData();

        $products = $this->productRepository
            ->findWhereIn('id', $this->productIds)
            ->keyBy('id');

        $products->loadMissing('parent.parent');

        if (app()->environment('testing')) {
            Log::debug('CompletenessJob', [
                'productIds'    => $this->productIds,
                'productsFound' => $products->keys()->toArray(),
                'channelCount'  => count($this->channels),
                'channels'      => collect($this->channels)->map(fn ($c): array => ['id' => $c['id'], 'code' => $c['code'], 'locales' => count($c['locales'])])->toArray(),
            ]);
        }

        $scoreRows = [];
        $avgScores = [];
        $deleteQueue = [];

        foreach ($this->productIds as $id) {
            $product = $products->get($id);

            if (! $product) {
                continue;
            }

            $productArray = $product->toArray();

            if (! empty($product->parent_id)) {
                $productArray['values'] = $product->resolvedValues();
            }

            [$rows, $avg, $deletes] = $this->computeProductCompleteness($productArray, $product);

            $scoreRows = array_merge($scoreRows, $rows);
            $avgScores[$id] = $avg;
            $deleteQueue = array_merge($deleteQueue, $deletes);
        }

        foreach ($deleteQueue as [$productId, $channelId]) {
            DB::table('product_completeness')
                ->where('product_id', $productId)
                ->where('channel_id', $channelId)
                ->delete();
        }

        if ($scoreRows !== []) {
            DB::table('product_completeness')->upsert(
                $scoreRows,
                ['product_id', 'channel_id', 'locale_id'],
                ['score', 'missing_count']
            );
        }

        if ($avgScores !== []) {
            $cases = '';
            $idList = implode(',', array_map(intval(...), array_keys($avgScores)));

            foreach ($avgScores as $pid => $score) {
                $cases .= ' WHEN '.((int) $pid).' THEN '.($score === null ? 'NULL' : (int) $score);
            }

            $prefix = DB::getTablePrefix();
            $castType = DB::getDriverName() === 'pgsql' ? 'INTEGER' : 'SIGNED';
            DB::statement("UPDATE {$prefix}products SET avg_completeness_score = CAST(CASE id {$cases} END AS {$castType}) WHERE id IN ({$idList})");
        }
    }

    protected function resolveDependencies(): void
    {
        $this->productRepository = resolve(ProductRepository::class);
        $this->channelRepository = resolve(ChannelRepository::class);
        $this->localeRepository = resolve(LocaleRepository::class);
        $this->attributeRepository = resolve(AttributeRepository::class);
        $this->completenessSettingsRepository = resolve(CompletenessSettingsRepository::class);
        $this->completenessResultsRepository = resolve(ProductCompletenessScoreRepository::class);
        $this->variantStructurePlanner = resolve(VariantStructurePlanner::class);
    }

    protected function loadStaticData(): void
    {
        $this->channels = $this->channelRepository
            ->skipCache()
            ->with([
                'locales' => function ($query): void {
                    $query->select('locales.id', 'locales.code')->where('status', 1)->orderBy('code');
                },
            ])
            ->get(['id', 'code'])
            ->map(fn ($channel): array => [
                'id'      => $channel->id,
                'code'    => $channel->code,
                'locales' => $channel->locales->map(fn ($locale): array => [
                    'id'   => $locale->id,
                    'code' => $locale->code,
                ])->values()->toArray(),
            ])
            ->toArray();
    }

    /** @return array{0: array, 1: int|null, 2: array} rows to upsert, avg score, orphan rows to delete */
    protected function computeProductCompleteness(array $product, ?Product $model = null): array
    {
        $familyId = $product['attribute_family_id'] ?? null;
        $productValues = $product['values'] ?? [];

        if (! $familyId) {
            return [[], null, []];
        }

        if (! isset($this->completenessSettings[$familyId])) {
            $this->completenessSettings[$familyId] = $this->completenessSettingsRepository
                ->findWhere(['family_id' => $familyId])
                ->groupBy('channel_id');
        }

        $settingsByChannel = $this->completenessSettings[$familyId];

        $channelCount = 0;
        $averageScore = 0;
        $scoreRows = [];
        $deleteQueue = [];

        foreach ($this->channels as $channel) {
            $channelId = $channel['id'];
            $channelCode = $channel['code'];
            $locales = $channel['locales'] ?? [];

            if (! isset($settingsByChannel[$channelId]) || empty($locales)) {
                $deleteQueue[] = [$product['id'], $channelId];

                continue;
            }

            $channelCount++;

            $attributeIds = collect($settingsByChannel[$channelId])->pluck('attribute_id')->all();

            $cacheKey = implode(',', $attributeIds);

            if (! isset($this->attributeCache[$cacheKey])) {
                $this->attributeCache[$cacheKey] = $this->attributeRepository
                    ->findWhereIn('id', $attributeIds)
                    ->keyBy('id');
            }

            $attributes = $this->ownedAttributes($this->attributeCache[$cacheKey], $model);

            [$channelScore, $channelRows] = $this->collectScoresForChannel(
                $product,
                $productValues,
                $channelId,
                $channelCode,
                $locales,
                $attributes
            );

            $averageScore += $channelScore;
            $scoreRows = array_merge($scoreRows, $channelRows);
        }

        $avgScore = $channelCount !== 0 ? round($averageScore / $channelCount) : null;

        return [$scoreRows, $avgScore, $deleteQueue];
    }

    /** Drop attributes a variant structure maintains below the product's own level. */
    protected function ownedAttributes($attributes, ?Product $model)
    {
        if (! $model instanceof Product) {
            return $attributes;
        }

        return $attributes->filter(
            fn ($attribute): bool => $this->variantStructurePlanner->ownsAttribute($model, $attribute->code)
        );
    }

    /** @return array{0: int, 1: array} channel score and rows to upsert */
    protected function collectScoresForChannel(
        array $product,
        array $productValues,
        int $channelId,
        string $channelCode,
        array $locales,
        $attributes
    ): array {
        $localizable = [];
        $nonLocalizable = [];

        foreach ($attributes as $attribute) {
            if ($attribute->isLocaleBasedAttribute()) {
                $localizable[] = $attribute;
            } else {
                $nonLocalizable[] = $attribute;
            }
        }

        $nonLocalizableTotal = 0;
        $nonLocalizableFilled = 0;

        foreach ($nonLocalizable as $attribute) {
            $nonLocalizableTotal++;

            $value = $attribute->getValueFromProductValues(
                $productValues,
                $channelCode,
                $locales[0]['code']
            );

            if (! empty($value)) {
                $nonLocalizableFilled++;
            }
        }

        $averageLocaleScore = 0;
        $missingCount = $nonLocalizableTotal - $nonLocalizableFilled;
        $rows = [];

        foreach ($locales as $locale) {
            $localeCode = $locale['code'];
            $localeId = $locale['id'];

            $filled = 0;
            $total = 0;

            foreach ($localizable as $attribute) {
                $total++;

                $value = $attribute->getValueFromProductValues(
                    $productValues,
                    $channelCode,
                    $localeCode
                );

                if (! empty($value)) {
                    $filled++;
                }
            }

            $total += $nonLocalizableTotal;
            $filled += $nonLocalizableFilled;

            $score = $total > 0 ? round(($filled / $total) * 100) : 100;

            $rows[] = [
                'product_id'    => $product['id'],
                'channel_id'    => $channelId,
                'locale_id'     => $localeId,
                'score'         => $score,
                'missing_count' => $missingCount + (($total - $nonLocalizableTotal) - ($filled - $nonLocalizableFilled)),
            ];

            $averageLocaleScore += $score;
        }

        return [round($averageLocaleScore / count($locales)), $rows];
    }
}
