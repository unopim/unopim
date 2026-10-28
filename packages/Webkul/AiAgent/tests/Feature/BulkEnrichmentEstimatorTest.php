<?php

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\Services\BulkEnrichmentEstimator;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\AiAgent\Services\TokenEstimator;
use Webkul\Product\Models\Product;

function estimatorSelection(int $count): Builder
{
    $ids = Product::factory()->count($count)->create()->pluck('id');

    return DB::table('products')->whereIn('id', $ids)->orderBy('id');
}

it('estimates the input tokens and output budget of every product in a small selection', function () {
    $selection = estimatorSelection(12);

    $expectedInput = (clone $selection)->get()->sum(function (object $row): int {
        [$ctx, $options] = app(EnrichmentService::class)->productContext(json_decode((string) $row->values, true) ?? [], 'default', 'en_US');

        return app(TokenEstimator::class)->estimateMessages(app(EnrichmentService::class)->previewMessages($ctx, $options));
    });

    $estimate = app(BulkEnrichmentEstimator::class)->estimate($selection, 1000, 'default', 'en_US');

    expect($estimate['products'])->toBe(12)
        ->and($estimate['sampled'])->toBe(12)
        ->and($estimate['sampled_estimate'])->toBeFalse()
        ->and($estimate['estimated_input_tokens'])->toBe($expectedInput)
        ->and($estimate['estimated_input_tokens'])->toBeGreaterThan(0)
        ->and($estimate['max_output_tokens'])->toBe(12 * EnrichmentService::MAX_OUTPUT_TOKENS);
});

it('samples a bounded number of products and extrapolates for a large selection', function () {
    DB::enableQueryLog();

    $estimate = app(BulkEnrichmentEstimator::class)->estimate(estimatorSelection(BulkEnrichmentEstimator::SAMPLE_SIZE + 10), 1000, 'default', 'en_US');

    $sampleQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'limit '.BulkEnrichmentEstimator::SAMPLE_SIZE));

    expect($estimate['products'])->toBe(BulkEnrichmentEstimator::SAMPLE_SIZE + 10)
        ->and($estimate['sampled'])->toBe(BulkEnrichmentEstimator::SAMPLE_SIZE)
        ->and($estimate['sampled_estimate'])->toBeTrue()
        ->and($sampleQueries)->not->toBeEmpty()
        ->and($estimate['max_output_tokens'])->toBe((BulkEnrichmentEstimator::SAMPLE_SIZE + 10) * EnrichmentService::MAX_OUTPUT_TOKENS);
});

it('caps the counted selection at the requested limit', function () {
    $estimate = app(BulkEnrichmentEstimator::class)->estimate(estimatorSelection(15), 11, 'default', 'en_US');

    expect($estimate['products'])->toBe(11);
});

it('counts no calls for products whose content is already filled', function () {
    $product = Product::factory()->create();
    $values = $product->values ?? [];

    foreach (['name', 'short_description', 'description', 'meta_title', 'meta_description', 'meta_keywords', 'product_number'] as $code) {
        $values['channel_locale_specific']['default']['en_US'][$code] = 'filled';
    }

    DB::table('products')->where('id', $product->id)->update(['values' => json_encode($values)]);

    $estimate = app(BulkEnrichmentEstimator::class)->estimate(DB::table('products')->where('id', $product->id), 10, 'default', 'en_US');

    expect($estimate['estimated_input_tokens'])->toBe(0)
        ->and($estimate['max_output_tokens'])->toBe(0);
});

it('logs the pre-flight estimate when one is produced', function () {
    Log::spy();

    $estimate = app(BulkEnrichmentEstimator::class)->estimate(estimatorSelection(3), 100, 'default', 'en_US');

    Log::shouldHaveReceived('info')->once()->withArgs(fn (string $message, array $context): bool => $message === 'AI Agent bulk enrichment token estimate'
        && $context['products'] === 3
        && $context['estimated_input_tokens'] === $estimate['estimated_input_tokens']
        && $context['max_output_tokens'] === $estimate['max_output_tokens']);
});

it('normalizes only a well formed estimate payload', function (mixed $payload, bool $valid) {
    $normalized = BulkEnrichmentEstimator::normalize($payload);

    expect($normalized !== null)->toBe($valid);
})->with([
    'valid'          => [['products' => 20, 'sampled' => 20, 'sampled_estimate' => false, 'estimated_input_tokens' => 9000, 'max_output_tokens' => 40960], true],
    'missing key'    => [['products' => 20, 'sampled' => 20, 'estimated_input_tokens' => 9000], false],
    'string count'   => [['products' => '20', 'sampled' => 20, 'sampled_estimate' => false, 'estimated_input_tokens' => 9000, 'max_output_tokens' => 1], false],
    'negative count' => [['products' => -1, 'sampled' => 20, 'sampled_estimate' => false, 'estimated_input_tokens' => 9000, 'max_output_tokens' => 1], false],
    'not an array'   => ['20 products', false],
]);

it('drops unknown keys from a normalized estimate', function () {
    $normalized = BulkEnrichmentEstimator::normalize([
        'products' => 20, 'sampled' => 20, 'sampled_estimate' => false, 'estimated_input_tokens' => 9000, 'max_output_tokens' => 40960, 'html' => '<script>',
    ]);

    expect($normalized)->not->toHaveKey('html');
});
