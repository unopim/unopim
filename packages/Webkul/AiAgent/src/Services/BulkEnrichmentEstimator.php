<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Pre-flight token estimate for bulk AI content generation over a product selection.
 *
 * Builds the exact messages EnrichmentService would send for a bounded sample
 * of the selection and extrapolates to the full count, so a huge selection is
 * never loaded into memory.
 *
 * @phpstan-type TokenEstimate array{products: int, sampled: int, sampled_estimate: bool, estimated_input_tokens: int, max_output_tokens: int}
 */
class BulkEnrichmentEstimator
{
    /**
     * Products whose prompts are built to derive the estimate.
     */
    public const SAMPLE_SIZE = 20;

    /**
     * Selections larger than this get the estimate shown on the confirmation step.
     */
    public const BULK_THRESHOLD = 10;

    public function __construct(
        protected EnrichmentService $enrichmentService,
        protected TokenEstimator $tokenEstimator,
    ) {}

    /**
     * Estimate the tokens one content generation call per product would use.
     *
     * The query must select the product `values` column.
     *
     * @return TokenEstimate
     */
    public function estimate(Builder $query, int $limit, string $channel, string $locale, string $instruction = ''): array
    {
        $products = DB::query()->fromSub((clone $query)->limit($limit), 'selection')->count();

        $sample = (clone $query)->limit(min(self::SAMPLE_SIZE, $products))->get(['values']);

        $sampleInput = 0;
        $sampleCalls = 0;

        foreach ($sample as $row) {
            [$ctx, $options] = $this->enrichmentService->productContext(
                json_decode((string) $row->values, true) ?: [],
                $channel,
                $locale,
                $instruction,
            );

            $messages = $this->enrichmentService->previewMessages($ctx, $options);

            if ($messages === []) {
                continue;
            }

            $sampleCalls++;
            $sampleInput += $this->tokenEstimator->estimateMessages($messages);
        }

        $sampled = $sample->count();
        $scale = $sampled > 0 ? $products / $sampled : 0;

        $estimate = [
            'products'               => $products,
            'sampled'                => $sampled,
            'sampled_estimate'       => $sampled < $products,
            'estimated_input_tokens' => (int) ceil($sampleInput * $scale),
            'max_output_tokens'      => (int) ceil($sampleCalls * $scale) * EnrichmentService::MAX_OUTPUT_TOKENS,
        ];

        Log::info('AI Agent bulk enrichment token estimate', $estimate + [
            'channel' => $channel,
            'locale'  => $locale,
        ]);

        return $estimate;
    }

    /**
     * Return the estimate when it has exactly the expected shape, null otherwise.
     *
     * @return TokenEstimate|null
     */
    public static function normalize(mixed $estimate): ?array
    {
        if (! is_array($estimate) || ! is_bool($estimate['sampled_estimate'] ?? null)) {
            return null;
        }

        $normalized = ['sampled_estimate' => $estimate['sampled_estimate']];

        foreach (['products', 'sampled', 'estimated_input_tokens', 'max_output_tokens'] as $key) {
            $value = $estimate[$key] ?? null;

            if (! is_int($value) || $value < 0) {
                return null;
            }

            $normalized[$key] = $value;
        }

        return [
            'products'               => $normalized['products'],
            'sampled'                => $normalized['sampled'],
            'sampled_estimate'       => $normalized['sampled_estimate'],
            'estimated_input_tokens' => $normalized['estimated_input_tokens'],
            'max_output_tokens'      => $normalized['max_output_tokens'],
        ];
    }
}
