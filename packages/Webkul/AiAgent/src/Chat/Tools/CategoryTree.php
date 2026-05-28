<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class CategoryTree implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'category_tree';
            }

            public function description(): string
            {
                return 'Get the full category tree hierarchy.';
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.categories')) {
                    return $denied;
                }

                $categories = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data')
                    ->orderBy('_lft')
                    ->limit(200)
                    ->get();

                $tree = $categories->map(function ($cat) {
                    $data = json_decode($cat->additional_data, true) ?? [];
                    $name = $data['locale_specific'][$this->context->locale]['name']
                        ?? $data['locale_specific']['en_US']['name']
                        ?? $cat->code;

                    return [
                        'id'        => $cat->id,
                        'code'      => $cat->code,
                        'name'      => $name,
                        'parent_id' => $cat->parent_id,
                    ];
                });

                return json_encode(['total' => $tree->count(), 'categories' => $tree->toArray()]);
            }
        };
    }
}
