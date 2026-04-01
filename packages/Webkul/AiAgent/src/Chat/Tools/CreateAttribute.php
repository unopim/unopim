<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class CreateAttribute implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('create_attribute')
            ->for('Create a new product attribute.')
            ->withStringParameter('code', 'Attribute code (auto-generated from name if not given)')
            ->withStringParameter('name', 'Attribute label/name (required)')
            ->withEnumParameter('type', 'Attribute type', ['text', 'textarea', 'boolean', 'select', 'multiselect', 'price', 'datetime', 'date', 'image', 'file', 'checkbox'])
            ->withBooleanParameter('is_required', 'Whether this attribute is required')
            ->withBooleanParameter('value_per_locale', 'Different value per locale')
            ->withBooleanParameter('value_per_channel', 'Different value per channel')
            ->withStringParameter('options', 'Comma-separated options for select/multiselect (e.g. "Red,Blue,Green")')
            ->using(function (
                ?string $code = null,
                ?string $name = null,
                string $type = 'text',
                bool $is_required = false,
                bool $value_per_locale = false,
                bool $value_per_channel = false,
                ?string $options = null,
            ) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.attributes')) {
                    return $denied;
                }

                if (! $name) {
                    return json_encode(['error' => 'Attribute name is required']);
                }

                $code = $code ?: Str::slug($name, '_');

                if (DB::table('attributes')->where('code', $code)->exists()) {
                    return json_encode(['error' => "Attribute code '{$code}' already exists"]);
                }

                $repo = app('Webkul\Attribute\Repositories\AttributeRepository');

                $data = [
                    'code'              => $code,
                    'type'              => $type,
                    'is_required'       => $is_required,
                    'value_per_locale'  => $value_per_locale,
                    'value_per_channel' => $value_per_channel,
                    'is_filterable'     => \in_array($type, ['select', 'multiselect', 'boolean']),
                    $context->locale    => ['name' => $name],
                ];

                // Add options for select/multiselect
                if ($options && \in_array($type, ['select', 'multiselect', 'checkbox'])) {
                    $optionItems = array_map('trim', explode(',', $options));
                    $optionsData = [];
                    foreach ($optionItems as $i => $opt) {
                        $optionsData['option_'.$i] = [
                            'isNew'           => 'true',
                            'code'            => Str::slug($opt, '_') ?: $opt,
                            'sort_order'      => $i + 1,
                            $context->locale  => ['label' => $opt],
                        ];
                    }
                    $data['options'] = $optionsData;
                }

                $attribute = $repo->create($data);

                return json_encode([
                    'result' => ['created' => true, 'id' => $attribute->id, 'code' => $code, 'type' => $type],
                ]);
            });
    }
}
