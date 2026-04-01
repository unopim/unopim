<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ListAttributes implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('list_attributes')
            ->for('List attributes for a product family with types and options.')
            ->withStringParameter('sku', 'Product SKU to get attributes for (uses its attribute family)')
            ->withNumberParameter('family_id', 'Attribute family ID (alternative to SKU)')
            ->using(function (?string $sku = null, ?int $family_id = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.attributes')) {
                    return $denied;
                }

                $familyId = $family_id;

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

                $result = $attributes->map(function ($attr) use ($context) {
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
                            ->leftJoin('attribute_option_translations as aot', function ($join) use ($context) {
                                $join->on('aot.attribute_option_id', '=', 'ao.id')
                                    ->where('aot.locale', '=', $context->locale);
                            })
                            ->where('ao.attribute_id', $attr->id)
                            ->select('ao.code', 'aot.label')
                            ->orderBy('ao.sort_order')
                            ->get()
                            ->map(fn ($o) => ['code' => $o->code, 'label' => $o->label ?? $o->code])
                            ->toArray();

                        $info['options'] = $options;
                    }

                    return $info;
                });

                return json_encode([
                    'family_id'  => $familyId,
                    'attributes' => $result->toArray(),
                ]);
            });
    }
}
