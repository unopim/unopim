<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageOptions implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_attribute_options')
            ->for('Add or list options for select/multiselect attributes.')
            ->withStringParameter('attribute_code', 'Attribute code (e.g. "color", "size")')
            ->withEnumParameter('action', 'Action to perform', ['list', 'add'])
            ->withStringParameter('options', 'Comma-separated option labels to add (e.g. "Purple,Orange")')
            ->using(function (string $attribute_code, string $action = 'list', ?string $options = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.attributes')) {
                    return $denied;
                }

                $attribute = DB::table('attributes')->where('code', $attribute_code)->first();
                if (! $attribute) {
                    return json_encode(['error' => "Attribute '{$attribute_code}' not found"]);
                }

                if ($action === 'list') {
                    $opts = DB::table('attribute_options as ao')
                        ->leftJoin('attribute_option_translations as aot', function ($join) use ($context) {
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
                    $items = array_map('trim', explode(',', $options));
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
            });
    }
}
