<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Core\Eloquent\Repository;

class AttributeFamilyRepository extends Repository
{
    /**
     * Create a new repository instance.
     *
     * @return void
     */
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected AttributeGroupRepository $attributeGroupRepository,
        protected AttributeFamilyGroupMappingRepository $attributeFamilyGroupMappingRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeFamily';
    }

    /**
     * @return \Webkul\Attribute\Contracts\AttributeFamily
     */
    public function create(array $data)
    {
        $attributeGroups = $data['attribute_groups'] ?? [];

        unset($data['attribute_groups']);

        $driver = DB::getDriverName();

        switch ($driver) {
            case 'pgsql':
                $sequence = $this->model->getTable().'_id_seq';
                DB::statement("SELECT setval('{$sequence}', (SELECT COALESCE(MAX(id), 0) + 1 FROM {$this->model->getTable()}), false)");
                break;

            case 'mysql':
            default:
                break;
        }

        $family = parent::create($data);

        foreach ($attributeGroups as $key => $group) {
            $customAttributes = $group['custom_attributes'] ?? [];

            unset($group['custom_attributes']);

            if ($driver === 'pgsql') {
                $mappingTable = $this->attributeFamilyGroupMappingRepository->getModel()->getTable();
                $mappingSeq = $mappingTable.'_id_seq';
                DB::statement("SELECT setval('{$mappingSeq}', (SELECT COALESCE(MAX(id), 0) + 1 FROM {$mappingTable}), false)");
            }

            $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->create([
                'attribute_family_id' => $family->id,
                'attribute_group_id'  => $key,
                'position'            => $group['position'],
            ]);

            foreach ($customAttributes as $key => $attribute) {
                if (isset($attribute['id'])) {
                    $attributeModel = $this->attributeRepository->find($attribute['id']);
                } else {
                    $attributeModel = $this->attributeRepository->findOneByField('code', $attribute['code']);
                }

                $familyGroupMapping->customAttributes()->save(
                    $attributeModel,
                    ['position' => $key + 1]
                );
            }
        }

        return $family;
    }

    /**
     * @param  int  $id
     * @param  string  $attribute
     * @return \Webkul\Attribute\Contracts\AttributeFamily
     */
    public function update(array $data, $id, $attribute = 'id')
    {
        $family = parent::update($data, $id, $attribute);
        $previousAttributeGroupMappingIds = $family->attributeFamilyGroupMappings()->pluck('id');

        $newValue = [];
        $oldValue = [];

        $addedAndRemovedAttributes = [
            'added'   => [],
            'removed' => [],
        ];

        foreach ($data['attribute_groups'] ?? [] as $attributeGroupId => $attributeGroupInputs) {
            $new = [];
            $old = [];

            $attributeGroupMappingId = $attributeGroupInputs['attribute_groups_mapping'];

            if (empty($attributeGroupMappingId)) {
                $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->create([
                    'attribute_family_id' => $family->id,
                    'attribute_group_id'  => $attributeGroupId,
                    'position'            => $attributeGroupInputs['position'],
                ]);

                $attributeGroup = $this->attributeGroupRepository->findWhere(['id' => $attributeGroupId]);
                $groupCode = $attributeGroup->first()?->toArray()['code'];
                $newValue['attribute_group'][] = $groupCode;
                if (empty($attributeGroupInputs['custom_attributes'])) {
                    continue;
                }

                foreach ($attributeGroupInputs['custom_attributes'] as $attributeInputs) {
                    $attribute = $this->attributeRepository->find($attributeInputs['id']);

                    $new[] = $attribute->toArray()['code'];

                    $familyGroupMapping->customAttributes()->save($attribute, [
                        'position' => $attributeInputs['position'],
                    ]);
                }
            } else {
                if (is_numeric($index = $previousAttributeGroupMappingIds->search($attributeGroupMappingId))) {
                    $previousAttributeGroupMappingIds->forget($index);
                }

                $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->update([
                    'attribute_family_id' => $family->id,
                    'attribute_group_id'  => $attributeGroupId,
                    'position'            => $attributeGroupInputs['position'],
                ], $attributeGroupMappingId);

                $attributeGroup = $this->attributeGroupRepository->findWhere(['id' => $attributeGroupId]);
                $groupCode = $attributeGroup->first()?->toArray()['code'];

                $newValue['attribute_group'][] = $groupCode;
                $oldValue['attribute_group'][] = $groupCode;

                $previousAttributeIds = $familyGroupMapping->customAttributes()->get()->pluck('id');

                foreach ($attributeGroupInputs['custom_attributes'] ?? [] as $attributeInputs) {
                    $attribute = $this->attributeRepository->find($attributeInputs['id']);
                    $code = $attribute?->toArray()['code'];
                    if (is_numeric($index = $previousAttributeIds->search($attributeInputs['id']))) {
                        $previousAttributeIds->forget($index);
                        $new[] = $code;
                        $old[] = $code;
                        $familyGroupMapping->customAttributes()->updateExistingPivot($attributeInputs['id'], [
                            'position' => $attributeInputs['position'],
                        ]);
                    } else {
                        $new[] = $code;
                        $familyGroupMapping->customAttributes()->save($attribute, [
                            'position' => $attributeInputs['position'],
                        ]);
                    }
                }

                if ($previousAttributeIds->count()) {
                    foreach ($previousAttributeIds as $attributeId) {
                        $attribute = $this->attributeRepository->find($attributeId);
                        $old[] = $attribute->toArray()['code'];
                    }
                    $familyGroupMapping->customAttributes()->detach($previousAttributeIds);
                }
            }

            $addedAndRemovedAttributes['added'] = array_merge($addedAndRemovedAttributes['added'], array_diff($new, $old));
            $addedAndRemovedAttributes['removed'] = array_merge($addedAndRemovedAttributes['removed'], array_diff($old, $new));

            $newValue[$groupCode] = implode(', ', $new);
            $oldValue[$groupCode] = implode(', ', $old);
        }

        foreach ($previousAttributeGroupMappingIds as $mappingId) {
            $attributeGroup = $this->attributeGroupRepository->find(['id' => $mappingId]);

            $oldValue['attribute_group'][] = $attributeGroup->first()?->toArray()['code'];

            $this->attributeFamilyGroupMappingRepository->delete($mappingId);
        }

        if (! empty($addedAndRemovedAttributes['added']) || ! empty($addedAndRemovedAttributes['removed'])) {
            Event::dispatch('catalog.attribute_family.attributes.changed', [
                'data'      => $addedAndRemovedAttributes['added'],
                'removed'   => $addedAndRemovedAttributes['removed'],
                'family_id' => $id,
            ]);
        }

        Event::dispatch('core.model.proxy.sync.AttributeFamilyGroupMapping', ['old_values' => $oldValue, 'new_values' => $newValue, 'model' => $familyGroupMapping]);

        return $family;
    }

    /**
     * @return array
     */
    public function getPartial()
    {
        $attributeFamilies = $this->model->all();

        $trimmed = [];

        foreach ($attributeFamilies as $key => $attributeFamily) {
            if (
                $attributeFamily->name != null
                || $attributeFamily->name != ''
            ) {
                $trimmed[$key] = [
                    'id'   => $attributeFamily->id,
                    'code' => $attributeFamily->code,
                    'name' => $attributeFamily->name,
                ];
            }
        }

        return $trimmed;
    }

    /**
     * This function returns a query builder instance for the family model.
     * It eager loads the 'translations' relationship for the family.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function queryBuilder()
    {
        return $this->with([
            'translations',
            'attributeFamilyGroupMappings.customAttributes',
        ]);
    }
}
