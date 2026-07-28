<?php

namespace Webkul\Attribute\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Contracts\AttributeFamily as AttributeFamilyContract;
use Webkul\Attribute\Database\Factories\AttributeFamilyFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Models\ProductProxy;

#[Fillable([
    'code',
    'status',
])]
#[WithoutTimestamps]
class AttributeFamily extends TranslatableModel implements AttributeFamilyContract, HistoryAuditable
{
    use HasFactory;
    use HistoryTrait;

    const ALLOWED_VARIANT_OPTION_TYPES = [
        'select',
    ];

    public $translatedAttributes = ['name'];

    /** Tags for History */
    protected $historyTags = ['attributeFamily'];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [
        'attribute_family_group_mappings',
        'attribute_group_mappings',
    ];

    protected $auditInclude = [
        'name',
        'code',
    ];

    protected ?Collection $customAttributesByGroup = null;

    /**
     * Get all the attributes for the attribute groups.
     */
    public function customAttributes()
    {
        return (AttributeProxy::modelClass())::join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', 'attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
            ->join('attribute_groups', 'attribute_family_group_mappings.attribute_group_id', '=', 'attribute_groups.id')
            ->join('attribute_families', 'attribute_family_group_mappings.attribute_family_id', '=', 'attribute_families.id')
            ->where('attribute_families.id', $this->id)
            ->with('translations')
            ->select('attributes.*', 'attribute_groups.id as group_id');
    }

    /**
     * Get all the attributes for the attribute groups.
     */
    protected function getCustomAttributesAttribute()
    {
        return $this->customAttributes()->get();
    }

    /**
     * This family's custom attributes keyed by attribute group id, each group in
     * the order the editor renders them. Memoized per instance because callers
     * (the product edit form) ask group by group, which otherwise repeats the
     * same four-table join once per group.
     *
     * @return Collection<int, Collection<int, Attribute>>
     */
    public function customAttributesByGroup(): Collection
    {
        return $this->customAttributesByGroup ??= $this->customAttributes()
            ->orderBy('attribute_group_mappings.position')
            ->get()
            ->groupBy('group_id');
    }

    /**
     * Label-only rows for every attribute assigned to this family, keyed by
     * attribute group id and ordered by their position within the group.
     *
     * Deliberately returns plain rows rather than models: the family editor
     * renders every group with all of its attributes, and a family with
     * thousands of groups exhausts memory long before Eloquent finishes
     * hydrating them. Names come from {@see translatedNames()}, which applies
     * the same locale/fallback resolution as the translatable accessor.
     *
     * @return Collection<int, Collection<int, object>>
     */
    public function attributeSummariesByGroup(string $localeCode): Collection
    {
        $names = $this->translatedNames('attribute_translations', 'attribute_id', $localeCode, fn ($query) => $query
            ->join(
                'attribute_group_mappings',
                'attribute_group_mappings.attribute_id',
                '=',
                'attribute_translations.attribute_id'
            )
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
        );

        return DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->join('attributes', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
            ->orderBy('attribute_group_mappings.position')
            ->select([
                'attributes.id',
                'attributes.code',
                'attributes.type',
                'attribute_group_mappings.position',
                'attribute_family_group_mappings.attribute_group_id as group_id',
            ])
            ->get()
            ->each(function ($attribute) use ($names): void {
                $attribute->name = $names[$attribute->id] ?? null;
            })
            ->groupBy('group_id');
    }

    /**
     * Resolve one translated `name` per translatable row, keyed by owner id,
     * preferring the requested locale over the configured fallback.
     *
     * Kept as a separate query rather than two aliased self-joins: Laravel
     * prefixes join aliases along with table names, so an aliased self-join
     * silently breaks on an installation configured with a table prefix.
     *
     * @param  callable(Builder): Builder  $scope
     * @return array<int, string>
     */
    protected function translatedNames(string $table, string $ownerKey, string $localeCode, callable $scope): array
    {
        $fallbackLocale = config('translatable.fallback_locale');

        $rows = $scope(DB::table($table))
            ->whereIn($table.'.locale', array_unique([$localeCode, $fallbackLocale]))
            ->select([$table.'.'.$ownerKey, $table.'.locale', $table.'.name'])
            ->get();

        $names = [];

        foreach ($rows as $row) {
            if ($row->locale === $localeCode || ! isset($names[$row->{$ownerKey}])) {
                $names[$row->{$ownerKey}] = $row->name;
            }
        }

        return $names;
    }

    /**
     * Label-only rows for this family's group mappings, in display order, for
     * the same reason as {@see attributeSummariesByGroup()}.
     *
     * @return Collection<int, object>
     */
    public function groupSummaries(string $localeCode): Collection
    {
        $names = $this->translatedNames('attribute_group_translations', 'attribute_group_id', $localeCode, fn ($query) => $query
            ->join(
                'attribute_family_group_mappings',
                'attribute_family_group_mappings.attribute_group_id',
                '=',
                'attribute_group_translations.attribute_group_id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
        );

        return DB::table('attribute_family_group_mappings')
            ->join(
                'attribute_groups',
                'attribute_groups.id',
                '=',
                'attribute_family_group_mappings.attribute_group_id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
            ->orderBy('attribute_family_group_mappings.position')
            ->select([
                'attribute_groups.id',
                'attribute_groups.code',
                'attribute_family_group_mappings.id as group_mapping_id',
                'attribute_family_group_mappings.position',
            ])
            ->get()
            ->each(function ($group) use ($names): void {
                $group->name = $names[$group->id] ?? null;
            });
    }

    /**
     * Get all the attribute groups.
     */
    public function attributeFamilyGroupMappings()
    {
        return $this->hasMany(AttributeFamilyGroupMappingProxy::modelClass())
            ->orderBy('position');
    }

    public function familyGroups()
    {
        return $this->belongsToMany(AttributeGroupProxy::modelClass(), 'attribute_family_group_mappings');
    }

    /**
     * Get all the attributes for the attribute groups.
     */
    public function getConfigurableAttributes()
    {
        return $this->customAttributes()
            ->whereIn('attributes.type', self::ALLOWED_VARIANT_OPTION_TYPES)
            ->where('attributes.value_per_locale', 0)
            ->where('attributes.value_per_channel', 0)
            ->get();
    }

    /**
     * Get all the products.
     */
    public function products(): HasMany
    {
        return $this->hasMany(ProductProxy::modelClass());
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return AttributeFamilyFactory::new();
    }
}
