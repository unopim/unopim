<?php

namespace Webkul\Completeness\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Completeness\Repositories\CompletenessSettingsRepository;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Product\Repositories\ProductRepository;

class ProductCompletenessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $productIds;

    protected ProductRepository $productRepository;

    protected ChannelRepository $channelRepository;

    protected LocaleRepository $localeRepository;

    protected AttributeRepository $attributeRepository;

    protected CompletenessSettingsRepository $completenessSettingsRepository;

    protected ProductCompletenessScoreRepository $completenessResultsRepository;

    protected array $channels = [];

    protected array $completenessSettings = [];

    protected array $attributeCache = [];

    public $tries = 3;

    public function __construct(array $productIds)
    {
        $this->productIds = $productIds;

        $this->queue = 'system';
    }

    public function handle(): void
    {
        $this->resolveDependencies();
        $this->loadStaticData();

        // Batch load all products in one query instead of find() per product
        $products = $this->productRepository
            ->findWhereIn('id', $this->productIds)
            ->keyBy('id');

        $scoreRows    = [];
        $avgScores    = [];
        $deleteQueue  = [];

        foreach ($this->productIds as $id) {
            $product = $products->get($id);

            if (! $product) {
                continue;
            }

            [$rows, $avg, $deletes] = $this->computeProductCompleteness($product->toArray());

            $scoreRows   = array_merge($scoreRows, $rows);
            $avgScores[$id] = $avg;
            $deleteQueue = array_merge($deleteQueue, $deletes);
        }

        // Bulk delete orphan channel completeness rows
        foreach ($deleteQueue as [$productId, $channelId]) {
            DB::table('product_completeness')
                ->where('product_id', $productId)
                ->where('channel_id', $channelId)
                ->delete();
        }

        // Bulk upsert all completeness scores in one query
        if (! empty($scoreRows)) {
            DB::table('product_completeness')->upsert(
                $scoreRows,
                ['product_id', 'channel_id', 'locale_id'],
                ['score', 'missing_count']
            );
        }

        // Bulk update avg_completeness_score with a single CASE statement
        if (! empty($avgScores)) {
            $cases  = '';
            $idList = implode(',', array_map('intval', array_keys($avgScores)));

            foreach ($avgScores as $pid => $score) {
                $cases .= ' WHEN '.((int) $pid).' THEN '.($score === null ? 'NULL' : (int) $score);
            }

            DB::statement("UPDATE products SET avg_completeness_score = CASE id {$cases} END WHERE id IN ({$idList})");
        }
    }

    protected function resolveDependencies(): void
    {
        $this->productRepository = app(ProductRepository::class);
        $this->channelRepository = app(ChannelRepository::class);
        $this->localeRepository = app(LocaleRepository::class);
        $this->attributeRepository = app(AttributeRepository::class);
        $this->completenessSettingsRepository = app(CompletenessSettingsRepository::class);
        $this->completenessResultsRepository = app(ProductCompletenessScoreRepository::class);
    }

    protected function loadStaticData(): void
    {
        $this->channels = $this->channelRepository
            ->with([
                'locales' => function ($query) {
                    $query->select('locales.id', 'locales.code')->where('status', 1)->orderBy('code');
                },
            ])
            ->get(['id', 'code'])
            ->map(function ($channel) {
                return [
                    'id'      => $channel->id,
                    'code'    => $channel->code,
                    'locales' => $channel->locales->map(function ($locale) {
                        return [
                            'id'   => $locale->id,
                            'code' => $locale->code,
                        ];
                    })->values()->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Compute completeness data for a product without writing to the DB.
     *
     * Returns [$scoreRows, $avgScore, $deleteQueue]:
     *   - $scoreRows:   rows ready for bulk upsert into product_completeness
     *   - $avgScore:    value to write to products.avg_completeness_score
     *   - $deleteQueue: [[productId, channelId], ...] orphan rows to delete
     */
    protected function computeProductCompleteness(array $product): array
    {
        $familyId      = $product['attribute_family_id'] ?? null;
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
        $scoreRows    = [];
        $deleteQueue  = [];

        foreach ($this->channels as $channel) {
            $channelId   = $channel['id'];
            $channelCode = $channel['code'];
            $locales     = $channel['locales'] ?? [];

            if (! isset($settingsByChannel[$channelId]) || empty($locales)) {
                $deleteQueue[] = [$product['id'], $channelId];

                continue;
            }

            $channelCount++;

            $attributeIds = collect($settingsByChannel[$channelId])->pluck('attribute_id')->all();

            // Cache attribute lookups to avoid repeated queries for the same attribute set
            $cacheKey = implode(',', $attributeIds);

            if (! isset($this->attributeCache[$cacheKey])) {
                $this->attributeCache[$cacheKey] = $this->attributeRepository
                    ->findWhereIn('id', $attributeIds)
                    ->keyBy('id');
            }

            $attributes = $this->attributeCache[$cacheKey];

            [$channelScore, $channelRows] = $this->collectScoresForChannel(
                $product,
                $productValues,
                $channelId,
                $channelCode,
                $locales,
                $attributes
            );

            $averageScore += $channelScore;
            $scoreRows     = array_merge($scoreRows, $channelRows);
        }

        $avgScore = $channelCount ? round($averageScore / $channelCount) : null;

        return [$scoreRows, $avgScore, $deleteQueue];
    }

    /**
     * Collect completeness score rows for a channel without writing to the DB.
     *
     * Returns [$channelScore, $rows] where $rows are ready for bulk upsert.
     */
    protected function collectScoresForChannel(
        array $product,
        array $productValues,
        int $channelId,
        string $channelCode,
        array $locales,
        $attributes
    ): array {
        $localizable    = [];
        $nonLocalizable = [];

        foreach ($attributes as $attribute) {
            if ($attribute->isLocaleBasedAttribute()) {
                $localizable[] = $attribute;
            } else {
                $nonLocalizable[] = $attribute;
            }
        }

        $nonLocalizableTotal  = 0;
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
        $missingCount       = $nonLocalizableTotal - $nonLocalizableFilled;
        $rows               = [];

        foreach ($locales as $locale) {
            $localeCode = $locale['code'];
            $localeId   = $locale['id'];

            $filled = 0;
            $total  = 0;

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

            $total  += $nonLocalizableTotal;
            $filled += $nonLocalizableFilled;

            $score = $total > 0 ? round(($filled / $total) * 100) : 0;

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
