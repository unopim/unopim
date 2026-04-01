<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;

class ManageFamilies implements PimTool
{
    use ChecksPermission;

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('manage_families')
            ->for('List, create, or inspect attribute families and their groups.')
            ->withEnumParameter('action', 'Action to perform', ['list', 'create', 'details'])
            ->withStringParameter('code', 'Family code (for create/details)')
            ->withStringParameter('name', 'Family name (for create)')
            ->using(function (string $action = 'list', ?string $code = null, ?string $name = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.families')) {
                    return $denied;
                }

                if ($action === 'list') {
                    $families = DB::table('attribute_families as af')
                        ->leftJoin('attribute_family_translations as aft', function ($join) use ($context) {
                            $join->on('aft.attribute_family_id', '=', 'af.id')
                                ->where('aft.locale', '=', $context->locale);
                        })
                        ->select('af.id', 'af.code', 'af.status', 'aft.name')
                        ->get();

                    return json_encode(['families' => $families->toArray()]);
                }

                if ($action === 'create') {
                    if (! $name) {
                        return json_encode(['error' => 'Family name is required']);
                    }
                    $code = $code ?: Str::slug($name, '_');

                    if (DB::table('attribute_families')->where('code', $code)->exists()) {
                        return json_encode(['error' => "Family '{$code}' already exists"]);
                    }

                    $repo = app('Webkul\Attribute\Repositories\AttributeFamilyRepository');
                    $family = $repo->create([
                        'code'           => $code,
                        'status'         => 1,
                        $context->locale => ['name' => $name],
                    ]);

                    return json_encode(['result' => ['created' => true, 'id' => $family->id, 'code' => $code]]);
                }

                if ($action === 'details' && $code) {
                    $family = DB::table('attribute_families')->where('code', $code)->first();
                    if (! $family) {
                        return json_encode(['error' => "Family '{$code}' not found"]);
                    }

                    $groups = DB::table('attribute_family_group_mappings as afgm')
                        ->join('attribute_groups as ag', 'ag.id', '=', 'afgm.attribute_group_id')
                        ->where('afgm.attribute_family_id', $family->id)
                        ->select('afgm.id as mapping_id', 'ag.code as group_code')
                        ->get();

                    $result = ['id' => $family->id, 'code' => $family->code, 'groups' => []];

                    foreach ($groups as $g) {
                        $attrs = DB::table('attribute_group_mappings as agm')
                            ->join('attributes as a', 'a.id', '=', 'agm.attribute_id')
                            ->where('agm.attribute_family_group_id', $g->mapping_id)
                            ->select('a.code', 'a.type')
                            ->get();

                        $result['groups'][] = [
                            'group'      => $g->group_code,
                            'attributes' => $attrs->pluck('code')->toArray(),
                        ];
                    }

                    return json_encode($result);
                }

                return json_encode(['error' => 'Invalid action']);
            });
    }
}
