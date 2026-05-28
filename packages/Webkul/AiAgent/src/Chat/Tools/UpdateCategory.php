<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class UpdateCategory implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'update_category';
            }

            public function description(): string
            {
                return 'Update a category name or parent.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'code'        => $schema->string()->description('Category code to update'),
                    'name'        => $schema->string()->description('New category name'),
                    'parent_code' => $schema->string()->description('New parent category code'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.categories.edit')) {
                    return $denied;
                }

                $code = $request->string('code')->toString();
                $name = $request->string('name')->toString() ?: null;
                $parent_code = $request->has('parent_code') ? ($request->string('parent_code')->toString() ?: null) : null;

                $category = DB::table('categories')->where('code', $code)->first();
                if (! $category) {
                    return json_encode(['error' => "Category '{$code}' not found"]);
                }

                $data = [];
                if ($name) {
                    $existing = json_decode($category->additional_data, true) ?? [];
                    $existing['locale_specific'][$this->context->locale]['name'] = $name;
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
            }
        };
    }
}
