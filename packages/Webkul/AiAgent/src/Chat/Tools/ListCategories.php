<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ListCategories implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('list_categories')
            ->for('List categories by code or name.')
            ->withStringParameter('search', 'Search term to filter categories by code or name')
            ->withNumberParameter('limit', 'Maximum results (default 20)')
            ->using(function (?string $search = null, int $limit = 20) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.categories')) {
                    return $denied;
                }

                $limit = min(max($limit, 1), 100);

                $qb = DB::table('categories')
                    ->select('id', 'code', 'parent_id', 'additional_data');

                if ($search) {
                    $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
                    $qb->where(function ($q) use ($escaped, $context) {
                        $q->where('code', 'like', "%{$escaped}%")
                            ->orWhereRaw("JSON_EXTRACT(additional_data, '$.locale_specific.{$context->locale}.name') LIKE ?", ["%{$escaped}%"]);
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
            });
    }
}
