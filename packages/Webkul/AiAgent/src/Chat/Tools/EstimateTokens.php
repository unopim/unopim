<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\TokenEstimator;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class EstimateTokens implements PimTool
{
    public function __construct(
        protected TokenEstimator $tokenEstimator,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $tokenEstimator = $this->tokenEstimator;

        return new class($context, $tokenEstimator) extends ContextualTool
        {
            use ChecksPermission;

            /**
             * Number of products sampled to derive the average per-product size.
             */
            private const int SAMPLE_SIZE = 20;

            /**
             * Instruction/formatting overhead added per AI call, in tokens.
             */
            private const int PER_CALL_OVERHEAD_TOKENS = 600;

            /**
             * Assumed generated output size per product, in tokens.
             */
            private const int OUTPUT_TOKENS_PER_PRODUCT = 400;

            public function __construct(
                ChatContext $context,
                protected TokenEstimator $tokenEstimator,
            ) {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'estimate_tokens';
            }

            public function description(): string
            {
                return 'Estimate the AI token cost of a bulk content operation BEFORE running it. Pass the same filter you would give bulk_edit / the products you plan to enrich; returns product count and estimated input/output/total tokens. Always call this before confirming bulk AI content generation over many products and include the estimate in your confirmation message.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'filter_by'    => $schema->string()->description('Filter products by')->enum(['status', 'category', 'family', 'all']),
                    'filter_value' => $schema->string()->description('Filter value (e.g. "active", category code, family code)'),
                    'limit'        => $schema->integer()->description('Max products the bulk operation would touch (default 50, max 1000)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                $filterBy = $request->string('filter_by')->toString() ?: 'all';
                $filterValue = $request->string('filter_value')->toString() ?: null;
                $limit = min(max($request->integer('limit', 50), 1), 1000);

                $grammar = GrammarQueryManager::getGrammar();

                $qb = DB::table('products')->select('id', 'values');

                if ($filterBy === 'status' && $filterValue !== null) {
                    $isActive = \in_array(strtolower($filterValue), ['active', 'enabled', '1', 'yes', 'on'], true);
                    $qb->where('status', $isActive ? 1 : 0);
                } elseif ($filterBy === 'category' && $filterValue) {
                    if (! preg_match('/^[a-zA-Z0-9_-]+$/', $filterValue)) {
                        return json_encode(['error' => 'Invalid category code.']);
                    }

                    $qb->whereRaw($grammar->jsonContains('values', ['categories'], '?'), ['"'.$filterValue.'"']);
                } elseif ($filterBy === 'family' && $filterValue) {
                    $familyId = DB::table('attribute_families')->where('code', $filterValue)->value('id');

                    if (! $familyId) {
                        return json_encode(['error' => "Family '{$filterValue}' not found"]);
                    }

                    $qb->where('attribute_family_id', $familyId);
                }

                $productCount = min((clone $qb)->count(), $limit);

                if ($productCount === 0) {
                    return json_encode(['error' => 'No products match the filter']);
                }

                $sample = $qb->limit(min(self::SAMPLE_SIZE, $productCount))->get();

                $sampleTokens = $sample->map(
                    fn ($row): int => $this->tokenEstimator->estimate((string) $row->values)
                );

                $avgInputPerProduct = (int) ceil($sampleTokens->avg() ?? 0) + self::PER_CALL_OVERHEAD_TOKENS;

                $estimatedInput = $avgInputPerProduct * $productCount;
                $estimatedOutput = self::OUTPUT_TOKENS_PER_PRODUCT * $productCount;

                return json_encode([
                    'products'                => $productCount,
                    'sampled'                 => $sample->count(),
                    'estimated_input_tokens'  => $estimatedInput,
                    'estimated_output_tokens' => $estimatedOutput,
                    'estimated_total_tokens'  => $estimatedInput + $estimatedOutput,
                    'note'                    => 'Heuristic estimate (~4 chars/token) based on a sample of the matched products; actual usage varies by model and prompt.',
                ]);
            }
        };
    }
}
