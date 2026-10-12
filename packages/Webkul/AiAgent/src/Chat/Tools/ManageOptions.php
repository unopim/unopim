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

class ManageOptions implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'manage_attribute_options';
            }

            public function description(): string
            {
                return 'Add or list options for select/multiselect attributes.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'attribute_code' => $schema->string()->description('Attribute code (e.g. "color", "size")'),
                    'action'         => $schema->string()->enum(['list', 'add'])->description('Action to perform'),
                    'options'        => $schema->string()->description('Comma-separated option labels to add (e.g. "Purple,Orange")'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.attributes')) {
                    return $denied;
                }

                $attribute_code = $request->string('attribute_code')->toString();
                $action = $request->string('action')->toString() ?: 'list';
                $options = $request->string('options')->toString() ?: null;

                $context = $this->context;

                $attribute = DB::table('attributes')->where('code', $attribute_code)->first();
                if (! $attribute) {
                    return json_encode(['error' => "Attribute '{$attribute_code}' not found"]);
                }

                if ($action === 'list') {
                    $opts = DB::table('attribute_options as ao')
                        ->leftJoin('attribute_option_translations as aot', function ($join) use ($context): void {
                            $join->on('aot.attribute_option_id', '=', 'ao.id')
                                ->where('aot.locale', '=', $context->locale);
                        })
                        ->where('ao.attribute_id', $attribute->id)
                        ->select('ao.id', 'ao.code', 'aot.label', 'ao.sort_order')
                        ->orderBy('ao.sort_order')
                        ->get();

                    return json_encode(['attribute' => $attribute_code, 'options' => $opts->toArray()]);
                }

                if ($action === 'add' && $options) {
                    $items = array_map(trim(...), explode(',', $options));
                    $added = [];
                    $maxSort = (int) DB::table('attribute_options')
                        ->where('attribute_id', $attribute->id)
                        ->max('sort_order');

                    foreach ($items as $label) {
                        $code = Str::slug($label, '_') ?: Str::lower($label);

                        $exists = DB::table('attribute_options')
                            ->where('attribute_id', $attribute->id)
                            ->where('code', $code)
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $maxSort++;
                        $optionId = DB::table('attribute_options')->insertGetId([
                            'attribute_id' => $attribute->id,
                            'code'         => $code,
                            'sort_order'   => $maxSort,
                        ]);

                        DB::table('attribute_option_translations')->insert([
                            'attribute_option_id' => $optionId,
                            'locale'              => $context->locale,
                            'label'               => $label,
                        ]);

                        $added[] = $code;
                    }

                    return json_encode(['result' => ['added' => $added, 'attribute' => $attribute_code]]);
                }

                return json_encode(['error' => 'Invalid action or missing options']);
            }
        };
    }
}
