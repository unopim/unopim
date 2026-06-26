<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Core\Helpers\Database\GrammarQueryManager;

class ListCategories implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'list_categories';
            }

            public function description(): string
            {
                return 'List categories by code or name.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'search' => $schema->string()->description('Search term to filter categories by code or name'),
                    'limit'  => $schema->integer()->description('Maximum results (default 20)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.categories')) {
                    return $denied;
                }

                $search = $request->string('search')->toString() ?: null;
                $limit = $request->has('limit') ? (int) $request->get('limit') : 20;

                $limit = min(max($limit, 1), 100);

                $context = $this->context;
                $grammar = GrammarQueryManager::getGrammar();

                $qb = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data');

                if ($search) {
                    $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
                    $qb->where(function ($q) use ($escaped, $context, $grammar) {
                        $q->where('code', 'like', "%{$escaped}%")
                            ->orWhereRaw($grammar->jsonExtract('additional_data', 'locale_specific', $context->locale, 'name').' LIKE ?', ["%{$escaped}%"]);
                    });
                }

                $categories = $qb->orderBy('_lft')->limit($limit)->get();

                $results = $categories->map(function ($cat) use ($context) {
                    $data = json_decode($cat->additional_data, true) ?? [];
                    $name = $data['locale_specific'][$context->locale]['name'] ?? $data['locale_specific']['en_US']['name'] ?? $cat->code;

                    return [
                        'id'        => $cat->id,
                        'code'      => $cat->code,
                        'name'      => $name,
                        'parent_id' => $cat->parent_id,
                    ];
                });

                return json_encode([
                    'total'      => $results->count(),
                    'categories' => $results->toArray(),
                ]);
            }
        };
    }
}
