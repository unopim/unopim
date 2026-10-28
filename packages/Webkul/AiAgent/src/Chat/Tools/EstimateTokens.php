<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\Services\BulkEnrichmentEstimator;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class EstimateTokens implements PimTool
{
    public function __construct(
        protected BulkEnrichmentEstimator $estimator,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $estimator = $this->estimator;

        return new class($context, $estimator) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(
                ChatContext $context,
                protected BulkEnrichmentEstimator $estimator,
            ) {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'estimate_tokens';
            }

            public function description(): string
            {
                return 'Estimate the AI token cost of a bulk content operation BEFORE running it. Pass the same filter you would give bulk_edit / the products you plan to enrich; returns product count, estimated input tokens and the maximum output budget of one generate_content call per product. Always call this before confirming bulk AI content generation over many products; the estimate is shown to the user next to your confirmation question.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'filter_by'    => $schema->string()->description('Filter products by')->enum(['status', 'category', 'family', 'all']),
                    'filter_value' => $schema->string()->description('Filter value (e.g. "active", category code, family code)'),
                    'limit'        => $schema->integer()->description('Max products the bulk operation would touch (default 50, max 1000)'),
                    'instruction'  => $schema->string()->description('Optional content instructions the generation would use'),
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

                $estimate = $this->estimator->estimate(
                    $qb->orderBy('id'),
                    $limit,
                    $this->context->channel,
                    $this->context->locale,
                    $request->string('instruction')->toString(),
                );

                if ($estimate['products'] === 0) {
                    return json_encode(['error' => 'No products match the filter']);
                }

                $payload = $estimate + [
                    'estimated_total_tokens' => $estimate['estimated_input_tokens'] + $estimate['max_output_tokens'],
                    'note'                   => 'Approximate (~4 chars/token): input from the generate_content prompts of up to '.BulkEnrichmentEstimator::SAMPLE_SIZE.' sampled products, extrapolated to the selection; output is the per-call max_tokens budget, an upper bound. No cost is shown because no per-model pricing is configured.',
                ];

                if ($estimate['products'] > BulkEnrichmentEstimator::BULK_THRESHOLD) {
                    $payload['token_estimate'] = $estimate;
                }

                return json_encode($payload);
            }
        };
    }
}
