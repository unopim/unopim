<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class CategoryTree implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('category_tree')
            ->for('Get the full category tree hierarchy.')
            ->using(function () use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.categories')) {
                    return $denied;
                }

                $categories = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data')
                    ->orderBy('_lft')
                    ->limit(200)
                    ->get();

                $tree = $categories->map(function ($cat) use ($context) {
                    $data = json_decode($cat->additional_data, true) ?? [];
                    $name = $data['locale_specific'][$context->locale]['name']
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
            });
    }
}
