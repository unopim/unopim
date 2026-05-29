<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ListAttributes implements PimTool
{
    public function register(ChatContext $context): Tool
    {
        return new class($context) extends ContextualTool
        {
            use ChecksPermission;

            public function name(): string
            {
                return 'list_attributes';
            }

            public function description(): string
            {
                return 'List attributes for a product family with types and options.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'sku'       => $schema->string()->description('Product SKU to get attributes for (uses its attribute family)'),
                    'family_id' => $schema->integer()->description('Attribute family ID (alternative to SKU)'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.attributes')) {
                    return $denied;
                }

                $sku = $request->string('sku')->toString() ?: null;
                $familyId = $request->has('family_id') ? (int) $request->get('family_id') : null;

                if ($sku && ! $familyId) {
                    $familyId = DB::table('products')
                        ->where('sku', $sku)
                        ->value('attribute_family_id');
                }

                if (! $familyId) {
                    $familyId = DB::table('attribute_families')->value('id');
                }

                if (! $familyId) {
                    return json_encode(['error' => 'No attribute family found']);
                }

                $attributes = DB::table('attributes as a')
                    ->join('attribute_group_mappings as agm', 'agm.attribute_id', '=', 'a.id')
                    ->join('attribute_family_group_mappings as afgm', 'afgm.id', '=', 'agm.attribute_family_group_id')
                    ->where('afgm.attribute_family_id', $familyId)
                    ->select('a.id', 'a.code', 'a.type', 'a.value_per_locale', 'a.value_per_channel', 'a.is_required')
                    ->orderBy('a.code')
                    ->get();

                $context = $this->context;

                $result = $attributes->map(function (\stdClass $attr) use ($context) {
                    $info = [
                        'code'              => $attr->code,
                        'type'              => $attr->type,
                        'required'          => (bool) $attr->is_required,
                        'value_per_locale'  => (bool) $attr->value_per_locale,
                        'value_per_channel' => (bool) $attr->value_per_channel,
                    ];

                    // Include options for select/multiselect attributes
                    if (\in_array($attr->type, ['select', 'multiselect'])) {
                        $options = DB::table('attribute_options as ao')
                            ->leftJoin('attribute_option_translations as aot', function (JoinClause $join) use ($context) {
                                $join->on('aot.attribute_option_id', '=', 'ao.id')
                                    ->where('aot.locale', '=', $context->locale);
                            })
                            ->where('ao.attribute_id', $attr->id)
                            ->select('ao.code', 'aot.label')
                            ->orderBy('ao.sort_order')
                            ->get()
                            ->map(fn (\stdClass $o) => ['code' => $o->code, 'label' => $o->label ?? $o->code])
                            ->toArray();

                        $info['options'] = $options;
                    }

                    return $info;
                });

                return json_encode([
                    'family_id'  => $familyId,
                    'attributes' => $result->toArray(),
                ]);
            }
        };
    }
}
