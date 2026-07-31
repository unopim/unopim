<?php

namespace Webkul\Attribute\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
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
     * Create a family with a usable starting structure: a clone of $basedOn, or a "general" group holding sku.
     */
    public function createScaffolded(string $code, ?int $basedOn = null, array $translations = []): AttributeFamily
    {
        $source = $basedOn ? $this->find($basedOn) : null;

        return DB::transaction(function () use ($code, $source, $translations) {
            $family = $this->create([
                'code'             => $code,
                'status'           => 1,
                'attribute_groups' => $source ? [] : $this->buildDefaultGroups(),
                ...$translations,
            ]);

            if ($source) {
                $this->copyGroupsFromSource($family, $source);

                Event::dispatch('catalog.attribute_family.copied', [
                    'family' => $family,
                    'source' => $source,
                ]);
            }

            return $family;
        });
    }

    /**
     * Mirror the source family's group/attribute layout. Attribute groups are global rows shared
     * across families, so the same group ids are re-mapped, never duplicated. The attribute rows
     * are copied by the database: a large family holds hundreds of thousands of them, which no
     * request can afford to hydrate as models.
     */
    protected function copyGroupsFromSource(AttributeFamily $family, AttributeFamily $source): void
    {
        $table = DB::getTablePrefix().'attribute_group_mappings';

        foreach ($source->attributeFamilyGroupMappings()->get() as $mapping) {
            $familyGroupMapping = $this->attributeFamilyGroupMappingRepository->create([
                'attribute_family_id' => $family->id,
                'attribute_group_id'  => $mapping->attribute_group_id,
                'position'            => $mapping->position,
            ]);

            DB::insert(
                "insert into {$table} (attribute_family_group_id, attribute_id, position)
                 select ?, attribute_id, position from {$table} where attribute_family_group_id = ?",
                [$familyGroupMapping->id, $mapping->id]
            );
        }
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

            $familyGroupMapping->customAttributes()->attach(
                $this->pivotRowsForAttributes($customAttributes)
            );
        }

        return $family;
    }

    /**
     * Pivot payload keyed by attribute id, so a group's attributes attach in one
     * insert instead of a lookup and an insert per attribute.
     *
     * @param  array<int, array{id?: int, code?: string}>  $customAttributes
     * @return array<int, array{position: int}>
     */
    protected function pivotRowsForAttributes(array $customAttributes): array
    {
        $codes = collect($customAttributes)
            ->reject(fn (array $attribute): bool => isset($attribute['id']))
            ->pluck('code')
            ->filter()
            ->all();

        $idsByCode = $codes === []
            ? collect()
            : $this->attributeRepository->getModel()->newQuery()
                ->whereIn('code', $codes)
                ->pluck('id', 'code');

        $rows = [];

        foreach (array_values($customAttributes) as $index => $attribute) {
            $id = $attribute['id'] ?? $idsByCode->get($attribute['code'] ?? '');

            if ($id) {
                $rows[(int) $id] = ['position' => $index + 1];
            }
        }

        return $rows;
    }

    /**
     * @param  int  $id
     * @return AttributeFamily
     */
    public function update(array $data, $id)
    {
        $family = parent::update($data, $id);
        $previousAttributeGroupMappingIds = $family->attributeFamilyGroupMappings()->toBase()->pluck('id');

        $newValue = [];
        $oldValue = [];

        $familyGroupMapping = null;

        $addedAndRemovedAttributes = [
            'added'   => [],
            'removed' => [],
        ];

        $groupPosition = 1;

        // Resolve all referenced attributes once, keyed by id, avoiding a find() per attribute per group.
        $attributeIds = collect($data['attribute_groups'] ?? [])
            ->flatMap(fn ($group): array => collect($group['custom_attributes'] ?? [])->pluck('id')->all())
            ->filter()
            ->unique()
            ->values()
            ->all();

        $attributesById = $attributeIds === []
            ? collect()
            : $this->attributeRepository->findWhereIn('id', $attributeIds)->keyBy('id');

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
                    $attribute = $attributesById->get($attributeInputs['id']);

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

                if (! $this->groupCarriesAttributes($attributeGroupInputs)) {
                    continue;
                }

                $previousAttributeIds = $familyGroupMapping->customAttributes()->get()->pluck('id');

                foreach ($attributeGroupInputs['custom_attributes'] ?? [] as $attributeIndex => $attributeInputs) {
                    $attribute = $attributesById->get($attributeInputs['id']);
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

        foreach ($this->removableGroupMappingIds($previousAttributeGroupMappingIds, $data) as $mappingId) {
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

    /**
     * Id/label pairs for family pickers. Deliberately not hydrating models: the translated
     * `name` accessor lazy-loads one query per family, which is unusable on a large catalog.
     *
     * @return Collection<int, array{id: int, label: string}>
     */
    public function getOptions(?string $locale = null): Collection
    {
        $locale ??= core()->getRequestedLocaleCode();

        return DB::table('attribute_families')
            ->leftJoin('attribute_family_translations', function ($join) use ($locale): void {
                $join->on('attribute_family_translations.attribute_family_id', '=', 'attribute_families.id')
                    ->where('attribute_family_translations.locale', '=', $locale);
            })
            ->orderBy('attribute_families.code')
            ->get([
                'attribute_families.id',
                'attribute_families.code',
                'attribute_family_translations.name',
            ])
            ->map(fn ($family): array => [
                'id'    => (int) $family->id,
                'label' => filled($family->name) ? $family->name : '['.$family->code.']',
            ])
            ->values();
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

    /**
     * Resolve which group mappings a save is allowed to delete.
     *
     * The editor paginates groups, so an absent group usually means "not on this
     * page" rather than "removed". When it sends `retained_group_mappings` -- the
     * mappings it still holds across every page -- only what is missing from that
     * list is removed. Payloads without the field stay authoritative.
     *
     * @param  Collection<int, int>  $previousMappingIds
     * @return array<int, int>
     */
    private function removableGroupMappingIds($previousMappingIds, array $data): array
    {
        if (! array_key_exists('retained_group_mappings', $data)) {
            return $previousMappingIds->all();
        }

        $retained = collect(is_array($data['retained_group_mappings'])
            ? $data['retained_group_mappings']
            : explode(',', (string) $data['retained_group_mappings']))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->all();

        return $previousMappingIds->reject(fn ($mappingId): bool => in_array((int) $mappingId, $retained, true))->all();
    }

    /**
     * Determine whether a submitted group carries an authoritative attribute list.
     *
     * The editor marks groups it never loaded `attributes_loaded=0` so their
     * assignments survive the save. Payloads without the flag are authoritative.
     */
    private function groupCarriesAttributes(array $inputs): bool
    {
        return ! array_key_exists('attributes_loaded', $inputs)
            || filter_var($inputs['attributes_loaded'], FILTER_VALIDATE_BOOLEAN);
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
     * Query builder with translations and family group mappings eager-loaded.
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
