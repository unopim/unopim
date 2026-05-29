<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\Category\Repositories\CategoryRepository;

class CreateCategory implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'create_category';
            }

            public function description(): string
            {
                return 'Create a new category in the catalog.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'code'        => $schema->string()->description('Unique category code (auto-generated from name if not provided)'),
                    'name'        => $schema->string()->description('Category name (required)'),
                    'parent_code' => $schema->string()->description('Parent category code for nesting (leave empty for root)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.categories.create')) {
                    return $denied;
                }

                $code = $request->string('code')->toString() ?: null;
                $name = $request->string('name')->toString() ?: null;
                $parent_code = $request->string('parent_code')->toString() ?: null;

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

                $repo = app(CategoryRepository::class);
                $category = $repo->create([
                    'code'            => $code,
                    'parent_id'       => $parentId,
                    'additional_data' => [
                        'locale_specific' => [
                            $this->context->locale => ['name' => $name],
                        ],
                    ],
                ]);

                return json_encode([
                    'result' => ['created' => true, 'id' => $category->id, 'code' => $code, 'name' => $name],
                ]);
            }
        };
    }
}
