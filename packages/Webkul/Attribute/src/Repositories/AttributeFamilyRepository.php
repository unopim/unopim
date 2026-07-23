<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Contracts\AttributeFamily;
use Webkul\Core\Eloquent\Repository;

class AttributeFamilyRepository extends Repository
{
    /**
     * Attribute group a family falls back to when no source family is chosen.
     */
    const DEFAULT_GROUP_CODE = 'general';

    /**
     * Attribute every family must start with.
     */
    const DEFAULT_ATTRIBUTE_CODE = 'sku';

    /**
     * Create a new repository instance.
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
        return AttributeFamily::class;
    }

    /**
     * Create a family with a usable starting structure: a clone of $basedOn when given,
     * otherwise a single "general" group holding the sku attribute.
     */
    public function createScaffolded(string $code, ?int $basedOn = null, array $translations = []): AttributeFamily
    {
        $source = $basedOn ? $this->find($basedOn) : null;

        return DB::transaction(function () use ($code, $source, $translations) {
            $family = $this->create([
                'code'             => $code,
                'status'           => 1,
                'attribute_groups' => $source
                    ? $this->buildGroupsFromSource($source)
                    : $this->buildDefaultGroups(),
                ...$translations,
            ]);

            if ($source) {
                Event::dispatch('catalog.attribute_family.copied', [
                    'family' => $family,
                    'source' => $source,
                ]);
            }

            return $family;
        });
    }

    /**
     * Mirror the source family's group/attribute layout as a create() payload. Attribute groups are
     * global rows shared across families, so the same group ids are re-mapped, never duplicated.
     */
    protected function buildGroupsFromSource(AttributeFamily $source): array
    {
        $groups = [];

        foreach ($source->attributeFamilyGroupMappings()->get() as $mapping) {
            $attributes = $mapping->customAttributes()
                ->orderBy('attribute_group_mappings.position')
                ->get();

            $groups[$mapping->attribute_group_id] = [
                'position'          => $mapping->position,
                'custom_attributes' => $attributes->map(fn ($attribute): array => [
                    'id' => $attribute->id,
                ])->values()->all(),
            ];
        }

        return $groups;
    }

    /**
     * A single "general" group holding sku. The group is recreated if an admin deleted it.
     */
    protected function buildDefaultGroups(): array
    {
        $group = $this->attributeGroupRepository->findOneByField('code', self::DEFAULT_GROUP_CODE)
            ?? $this->attributeGroupRepository->create([
                'code' => self::DEFAULT_GROUP_CODE,
            ]);

        return [
            $group->id => [
                'position'          => 1,
                'custom_attributes' => [
                    ['code' => self::DEFAULT_ATTRIBUTE_CODE],
                ],
            ],
        ];
    }

    /**
     * @return AttributeFamily
     */
    public function create(array $data)
    {
        $attributeGroups = $data['attribute_groups'] ?? [];

        unset($data['attribute_groups']);

        $family = parent::create($data);

        $groupPosition = 1;

        foreach ($attributeGroups as $groupId => $group) {
            $attributeGroupId = $this->resolveAttributeGroupId($groupId, $group);

            if (! $attributeGroupId) {
                continue;
            }

            $customAttributes = $group['custom_attributes'] ?? [];

            unset($group['custom_attributes']);

            $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->create([
                'attribute_family_id' => $family->id,
                'attribute_group_id'  => $attributeGroupId,
                'position'            => $group['position'] ?? $groupPosition,
            ]);

            $groupPosition++;

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
     * @return AttributeFamily
     */
    public function update(array $data, $id)
    {
        $family = parent::update($data, $id);
        $previousAttributeGroupMappingIds = $family->attributeFamilyGroupMappings()->pluck('id');

        $newValue = [];
        $oldValue = [];

        $familyGroupMapping = null;

        $addedAndRemovedAttributes = [
            'added'   => [],
            'removed' => [],
        ];

        $groupPosition = 1;

        foreach ($data['attribute_groups'] ?? [] as $groupId => $attributeGroupInputs) {
            $new = [];
            $old = [];

            $attributeGroupId = $this->resolveAttributeGroupId($groupId, $attributeGroupInputs);

            if (! $attributeGroupId) {
                continue;
            }

            $attributeGroupMappingId = $attributeGroupInputs['attribute_groups_mapping'] ?? null;
            $attributeGroupPosition = $attributeGroupInputs['position'] ?? $groupPosition;

            $groupPosition++;

            if (empty($attributeGroupMappingId)) {
                $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->create([
                    'attribute_family_id' => $family->id,
                    'attribute_group_id'  => $attributeGroupId,
                    'position'            => $attributeGroupPosition,
                ]);

                $attributeGroup = $this->attributeGroupRepository->findWhere(['id' => $attributeGroupId]);
                $groupCode = $attributeGroup->first()?->toArray()['code'];
                $newValue['attribute_group'][] = $groupCode;
                if (empty($attributeGroupInputs['custom_attributes'])) {
                    continue;
                }

                foreach ($attributeGroupInputs['custom_attributes'] as $attributeIndex => $attributeInputs) {
                    $attribute = $this->attributeRepository->find($attributeInputs['id']);

                    $new[] = $attribute->toArray()['code'];

                    $familyGroupMapping->customAttributes()->save($attribute, [
                        'position' => $attributeInputs['position'] ?? ($attributeIndex + 1),
                    ]);
                }
            } else {
                if (is_numeric($index = $previousAttributeGroupMappingIds->search($attributeGroupMappingId))) {
                    $previousAttributeGroupMappingIds->forget($index);
                }

                $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->update([
                    'attribute_family_id' => $family->id,
                    'attribute_group_id'  => $attributeGroupId,
                    'position'            => $attributeGroupPosition,
                ], $attributeGroupMappingId);

                $attributeGroup = $this->attributeGroupRepository->findWhere(['id' => $attributeGroupId]);
                $groupCode = $attributeGroup->first()?->toArray()['code'];

                $newValue['attribute_group'][] = $groupCode;
                $oldValue['attribute_group'][] = $groupCode;

                $previousAttributeIds = $familyGroupMapping->customAttributes()->get()->pluck('id');

                foreach ($attributeGroupInputs['custom_attributes'] ?? [] as $attributeIndex => $attributeInputs) {
                    $attribute = $this->attributeRepository->find($attributeInputs['id']);
                    $code = $attribute?->toArray()['code'];
                    $attributePosition = $attributeInputs['position'] ?? ($attributeIndex + 1);

                    if (is_numeric($index = $previousAttributeIds->search($attributeInputs['id']))) {
                        $previousAttributeIds->forget($index);
                        $new[] = $code;
                        $old[] = $code;
                        $familyGroupMapping->customAttributes()->updateExistingPivot($attributeInputs['id'], [
                            'position' => $attributePosition,
                        ]);
                    } else {
                        $new[] = $code;
                        $familyGroupMapping->customAttributes()->save($attribute, [
                            'position' => $attributePosition,
                        ]);
                    }
                }

                if ($previousAttributeIds->count()) {
                    $old = array_merge(
                        $old,
                        $this->attributeRepository->findWhereIn('id', $previousAttributeIds->all())->pluck('code')->all()
                    );
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

        if (isset($addedAndRemovedAttributes['added']) && $addedAndRemovedAttributes['added'] !== [] || isset($addedAndRemovedAttributes['removed']) && $addedAndRemovedAttributes['removed'] !== []) {
            Event::dispatch('catalog.attribute_family.attributes.changed', [
                'data'      => $addedAndRemovedAttributes['added'],
                'removed'   => $addedAndRemovedAttributes['removed'],
                'family_id' => $id,
            ]);
        }

        if ($familyGroupMapping) {
            Event::dispatch('core.model.proxy.sync.AttributeFamilyGroupMapping', ['old_values' => $oldValue, 'new_values' => $newValue, 'model' => $familyGroupMapping]);
        }

        return $family;
    }

    public function getPartial(): array
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

    private function resolveAttributeGroupId(int|string $groupId, array $inputs): ?int
    {
        $inputId = $inputs['id'] ?? null;

        if (is_numeric($inputId)) {
            return (int) $inputId;
        }

        if (is_numeric($groupId)) {
            return (int) $groupId;
        }

        if (! empty($inputs['code'])) {
            return $this->attributeGroupRepository->findOneByField('code', $inputs['code'])?->id;
        }

        return null;
    }

    /**
     * This function returns a query builder instance for the family model.
     * It eager loads the 'translations' relationship for the family.
     *
     * @return Builder
     */
    public function queryBuilder()
    {
        return $this->with([
            'translations',
            'attributeFamilyGroupMappings.customAttributes',
        ]);
    }
}
