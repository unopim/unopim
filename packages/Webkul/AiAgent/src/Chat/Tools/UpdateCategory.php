<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class UpdateCategory implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('update_category')
            ->for('Update a category name or parent.')
            ->withStringParameter('code', 'Category code to update')
            ->withStringParameter('name', 'New category name')
            ->withStringParameter('parent_code', 'New parent category code')
            ->using(function (string $code, ?string $name = null, ?string $parent_code = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.categories.edit')) {
                    return $denied;
                }

                $category = DB::table('categories')->where('code', $code)->first();
                if (! $category) {
                    return json_encode(['error' => "Category '{$code}' not found"]);
                }

                $data = [];
                if ($name) {
                    $existing = json_decode($category->additional_data, true) ?? [];
                    $existing['locale_specific'][$context->locale]['name'] = $name;
                    $data['additional_data'] = json_encode($existing);
                }

                if ($parent_code !== null) {
                    $parent = DB::table('categories')->where('code', $parent_code)->first();
                    $data['parent_id'] = $parent ? $parent->id : null;
                }

                if (! empty($data)) {
                    DB::table('categories')->where('id', $category->id)->update($data);
                }

                return json_encode(['result' => ['updated' => true, 'code' => $code]]);
            });
    }
}
