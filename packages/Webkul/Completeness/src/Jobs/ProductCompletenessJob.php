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

        foreach ($this->productIds as $id) {
            $product = $this->productRepository->find($id);

            if ($product) {
                $this->processProductCompleteness($product->toArray());
            }
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

    protected function processProductCompleteness(array $product): void
    {
        $familyId = $product['attribute_family_id'] ?? null;
        $productValues = $product['values'] ?? [];

        if (! $familyId) {
            return;
        }

        if (! isset($this->completenessSettings[$familyId])) {
            $this->completenessSettings[$familyId] = $this->completenessSettingsRepository
                ->findWhere(['family_id' => $familyId])
                ->groupBy('channel_id');
        }

        $settingsByChannel = $this->completenessSettings[$familyId];

        $channelCount = 0;

        $averageScore = 0;

        foreach ($this->channels as $channel) {
            $channelId = $channel['id'];
            $channelCode = $channel['code'];
            $locales = $channel['locales'] ?? [];

            if (! isset($settingsByChannel[$channelId]) || empty($locales)) {
                // Remove existing completeness result for this channel and product if any exists
                $this->completenessResultsRepository
                    ->where(['product_id' => $product['id'], 'channel_id' => $channelId])
                    ->delete();

                continue;
            }

            $channelCount++;

            $attributeIds = collect($settingsByChannel[$channelId])->pluck('attribute_id')->all();
            $attributes = $this->attributeRepository->findWhereIn('id', $attributeIds)->keyBy('id');

            $averageScore += $this->calculateScoresForChannel(
                $product,
                $productValues,
                $channelId,
                $channelCode,
                $locales,
                $attributes
            );
        }

        $averageScore = round($averageScore / $channelCount);

        DB::table('products')->where('id', $product['id'])->update(['avg_completeness_score' => $averageScore]);
    }

    protected function calculateScoresForChannel(
        array $product,
        array $productValues,
        int $channelId,
        string $channelCode,
        array $locales,
        $attributes
    ): int {
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

        foreach ($locales as $index => $locale) {
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

            $score = $total > 0 ? round(($filled / $total) * 100) : 0;

            $this->completenessResultsRepository->updateOrCreate([
                'product_id' => $product['id'],
                'channel_id' => $channelId,
                'locale_id'  => $localeId,
            ], [
                'score'         => $score,
                'missing_count' => $missingCount + (($total - $nonLocalizableTotal) - ($filled - $nonLocalizableFilled)),
            ]);

            $averageLocaleScore += $score;
        }

        return round($averageLocaleScore / count($locales));
    }
}
