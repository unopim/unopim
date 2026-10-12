<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Completeness\Repositories\ProductCompletenessScoreRepository;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\ProductProxy;

use function Pest\Laravel\get;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->scoreRepository = app(ProductCompletenessScoreRepository::class);
});

/**
 * Seeds a spread of scores across two locales of a channel so the grouped
 * aggregates have something with a non-trivial average to get wrong.
 */
function seedCompletenessScores(): array
{
    $channel = ChannelProxy::first();

    $locales = $channel->locales()->take(2)->get();

    $familyId = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create()->id;

    $scores = [40, 60, 100];

    foreach ($scores as $index => $score) {
        $product = ProductProxy::factory()->create(['attribute_family_id' => $familyId]);

        foreach ($locales as $offset => $locale) {
            ProductCompletenessScore::create([
                'product_id'    => $product->id,
                'channel_id'    => $channel->id,
                'locale_id'     => $locale->id,
                'score'         => $score - ($offset * 10),
                'missing_count' => $index,
            ]);
        }
    }

    return [$channel, $locales];
}

it('matches the per-row averages it replaces', function () {
    [$channel, $locales] = seedCompletenessScores();

    $byChannel = $this->scoreRepository->getAverageScoresByChannel();
    $byLocale = $this->scoreRepository->getAverageScoresByChannelAndLocale();
    $counts = $this->scoreRepository->countProductsWithCompletenessByChannel();

    expect(round($byChannel[$channel->id], 6))
        ->toBe(round((float) $this->scoreRepository->getAverageScore($channel->id), 6));

    expect($counts[$channel->id])
        ->toBe($this->scoreRepository->countProductsWithCompletenessCalculated($channel->id));

    foreach ($locales as $locale) {
        expect(round($byLocale[$channel->id][$locale->id], 6))
            ->toBe(round((float) $this->scoreRepository->getAverageScore($channel->id, $locale->id), 6));
    }
});

/**
 * One grouped count and two grouped averages answer the whole panel; the
 * previous shape asked for each figure per channel and per channel-locale pair,
 * so the count tracked the size of the channel matrix.
 */
it('reads the score table a fixed number of times whatever the channel spread', function () {
    seedCompletenessScores();

    $scoreQueries = 0;

    DB::listen(function ($query) use (&$scoreQueries): void {
        if (str_contains($query->sql, 'product_completeness')) {
            $scoreQueries++;
        }
    });

    get(route('admin.dashboard.completeness.data'), ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($scoreQueries)->toBe(3);
});

it('reports the seeded averages for the channel', function () {
    [$channel, $locales] = seedCompletenessScores();

    $data = get(route('admin.dashboard.completeness.data'), ['Accept' => 'application/json'])
        ->assertOk()
        ->json('data');

    expect($data[$channel->code]['average'])->toEqual(round((float) $this->scoreRepository->getAverageScore($channel->id)));

    foreach ($locales as $locale) {
        expect($data[$channel->code]['locales'][$locale->name])
            ->toEqual(round((float) $this->scoreRepository->getAverageScore($channel->id, $locale->id)));
    }
});
