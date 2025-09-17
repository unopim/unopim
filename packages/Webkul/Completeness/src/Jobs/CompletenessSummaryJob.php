<?php

namespace Webkul\Completeness\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\CoreConfigRepository;

class CompletenessSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ChannelRepository $channelRepository;

    protected ProductCompletenessScoreRepository $scoreRepository;

    protected CoreConfigRepository $coreConfigRepository;

    public function __construct() {}

    public function handle(): void
    {
        $channels = $this->channelRepository->all();

        foreach ($channels as $channel) {
            $this->processChannel($channel->id, $channel->code, $channel->locales);
        }
    }

    protected function processChannel(int $channelId, string $channelCode, iterable $locales): void
    {
        $localeScores = [];

        foreach ($locales as $locale) {
            $avgScore = $this->scoreRepository
                ->where('channel_id', $channelId)
                ->where('locale_id', $locale->id)
                ->avg('score');

            if ($avgScore !== null) {
                $roundedScore = round($avgScore, 2);

                $this->storeScore(
                    code: 'completeness.summary.score',
                    value: $roundedScore,
                    channelCode: $channelCode,
                    localeCode: $locale->code
                );

                $localeScores[] = $roundedScore;
            }
        }

        if (! empty($localeScores)) {
            $channelAvg = round(array_sum($localeScores) / count($localeScores), 2);

            $this->storeScore(
                code: 'completeness.summary.channel_score',
                value: $channelAvg,
                channelCode: $channelCode
            );
        }
    }

    protected function storeScore(string $code, float $value, string $channelCode, ?string $localeCode = null): void
    {
        $this->coreConfigRepository->updateOrCreate(
            [
                'code'         => $code,
                'channel_code' => $channelCode,
                'locale_code'  => $localeCode,
            ],
            ['value' => $value]
        );
    }

    protected function resolveDependencies(): void
    {
        $this->channelRepository = app(ChannelRepository::class);
        $this->scoreRepository = app(ProductCompletenessScoreRepository::class);
        $this->coreConfigRepository = app(CoreConfigRepository::class);
    }
}
