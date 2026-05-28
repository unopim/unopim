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

class CreateAttribute implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'create_attribute';
            }

            public function description(): string
            {
                return 'Create a new product attribute.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'code'              => $schema->string()->description('Attribute code (auto-generated from name if not given)'),
                    'name'              => $schema->string()->description('Attribute label/name (required)'),
                    'type'              => $schema->string()->description('Attribute type')->enum(['text', 'textarea', 'boolean', 'select', 'multiselect', 'price', 'datetime', 'date', 'image', 'file', 'checkbox']),
                    'is_required'       => $schema->boolean()->description('Whether this attribute is required'),
                    'value_per_locale'  => $schema->boolean()->description('Different value per locale'),
                    'value_per_channel' => $schema->boolean()->description('Different value per channel'),
                    'options'           => $schema->string()->description('Comma-separated options for select/multiselect (e.g. "Red,Blue,Green")'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.attributes')) {
                    return $denied;
                }

                $code = $request->string('code')->toString() ?: null;
                $name = $request->string('name')->toString() ?: null;
                $type = $request->string('type')->toString() ?: 'text';
                $is_required = $request->boolean('is_required');
                $value_per_locale = $request->boolean('value_per_locale');
                $value_per_channel = $request->boolean('value_per_channel');
                $options = $request->string('options')->toString() ?: null;

                if (! $name) {
                    return json_encode(['error' => 'Attribute name is required']);
                }

                $code = $code ?: Str::slug($name, '_');

                if (DB::table('attributes')->where('code', $code)->exists()) {
                    return json_encode(['error' => "Attribute code '{$code}' already exists"]);
                }

                $repo = app('Webkul\Attribute\Repositories\AttributeRepository');

                $data = [
                    'code'                 => $code,
                    'type'                 => $type,
                    'is_required'          => $is_required,
                    'value_per_locale'     => $value_per_locale,
                    'value_per_channel'    => $value_per_channel,
                    'is_filterable'        => \in_array($type, ['select', 'multiselect', 'boolean']),
                    $this->context->locale => ['name' => $name],
                ];

                // Add options for select/multiselect
                if ($options && \in_array($type, ['select', 'multiselect', 'checkbox'])) {
                    $optionItems = array_map('trim', explode(',', $options));
                    $optionsData = [];
                    foreach ($optionItems as $i => $opt) {
                        $optionsData['option_'.$i] = [
                            'isNew'                => 'true',
                            'code'                 => Str::slug($opt, '_') ?: $opt,
                            'sort_order'           => $i + 1,
                            $this->context->locale => ['label' => $opt],
                        ];
                    }
                    $data['options'] = $optionsData;
                }

                $attribute = $repo->create($data);

                return json_encode([
                    'result' => ['created' => true, 'id' => $attribute->id, 'code' => $code, 'type' => $type],
                ]);
            }
        };
    }
}
