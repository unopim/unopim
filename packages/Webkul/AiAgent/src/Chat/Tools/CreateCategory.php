<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class CreateCategory implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('create_category')
            ->for('Create a new category in the catalog.')
            ->withStringParameter('code', 'Unique category code (auto-generated from name if not provided)')
            ->withStringParameter('name', 'Category name (required)')
            ->withStringParameter('parent_code', 'Parent category code for nesting (leave empty for root)')
            ->using(function (?string $code = null, ?string $name = null, ?string $parent_code = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.categories.create')) {
                    return $denied;
                }

                if (! $name) {
                    return json_encode(['error' => 'Category name is required']);
                }

                $code = $code ?: Str::slug($name);

                if (DB::table('categories')->where('code', $code)->exists()) {
                    return json_encode(['error' => "Category code '{$code}' already exists"]);
                }

                $parentId = null;
                if ($parent_code) {
                    $parent = DB::table('categories')->where('code', $parent_code)->first();
                    if (! $parent) {
                        return json_encode(['error' => "Parent category '{$parent_code}' not found"]);
                    }
                    $parentId = $parent->id;
                }

                $repo = app('Webkul\Category\Repositories\CategoryRepository');
                $category = $repo->create([
                    'code'            => $code,
                    'parent_id'       => $parentId,
                    'additional_data' => [
                        'locale_specific' => [
                            $context->locale => ['name' => $name],
                        ],
                    ],
                ]);

                return json_encode([
                    'result' => ['created' => true, 'id' => $category->id, 'code' => $code, 'name' => $name],
                ]);
            });
    }
}
