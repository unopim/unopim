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
        'variant_structures',
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
     * Get this family's custom attributes keyed by attribute group id.
     *
     * Memoized because callers ask group by group, which would otherwise repeat
     * the same join once per group.
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
     * Get label-only attribute rows keyed by attribute group id.
     *
     * Returns plain rows rather than models because hydrating every attribute
     * of a large family exhausts memory.
     *
     * @return Collection<int, Collection<int, object>>
     */
    public function attributeSummariesByGroup(string $localeCode, ?int $groupId = null): Collection
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
            ->when($groupId, fn ($builder) => $builder->where('attribute_family_group_mappings.attribute_group_id', $groupId))
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
            ->when($groupId, fn ($builder) => $builder->where('attribute_family_group_mappings.attribute_group_id', $groupId))
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
     * Get the number of attributes assigned to each of this family's groups.
     *
     * @return Collection<int, int>
     */
    public function attributeCountsByGroup(): Collection
    {
        return DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
            ->groupBy('attribute_family_group_mappings.attribute_group_id')
            ->select([
                'attribute_family_group_mappings.attribute_group_id',
                DB::raw('count(*) as attributes_count'),
            ])
            ->get()
            ->mapWithKeys(fn ($row): array => [$row->attribute_group_id => (int) $row->attributes_count]);
    }

    /**
     * Get how many attributes are assigned to this family.
     *
     * Counted in SQL because callers use it to decide whether the family is
     * small enough to render whole; hydrating it to find out would defeat the
     * purpose.
     */
    public function attributeCount(): int
    {
        return DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
            ->count();
    }

    /**
     * Get the hydrated attributes of a single group of this family.
     *
     * Unlike {@see customAttributesByGroup()} this never touches the family's
     * other groups, so its cost is bounded by the group rather than by the
     * family.
     *
     * An attribute assigned to several of the family's groups belongs to the
     * first one in display order: every copy renders the same input name, so a
     * later duplicate would overwrite whatever the editor typed into the first.
     *
     * @return Collection<int, Attribute>
     */
    public function customAttributesForGroup(int $groupId): Collection
    {
        $attributes = $this->customAttributes()
            ->where('attribute_groups.id', $groupId)
            ->orderBy('attribute_group_mappings.position')
            ->get();

        if ($attributes->isEmpty()) {
            return $attributes;
        }

        $claimedEarlier = DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_group_mappings.attribute_family_group_id',
                '=',
                'attribute_family_group_mappings.id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $this->id)
            ->where('attribute_family_group_mappings.position', '<', $this->groupPosition($groupId))
            ->whereIn('attribute_group_mappings.attribute_id', $attributes->pluck('id')->all())
            ->pluck('attribute_group_mappings.attribute_id')
            ->all();

        return $attributes->whereNotIn('id', $claimedEarlier)->values();
    }

    /**
     * Get a group's display position within this family.
     */
    protected function groupPosition(int $groupId): int
    {
        return (int) DB::table('attribute_family_group_mappings')
            ->where('attribute_family_id', $this->id)
            ->where('attribute_group_id', $groupId)
            ->value('position');
    }

    /**
     * Get one of this family's groups by code, falling back to the first group
     * in display order when the code is absent or does not belong to it.
     */
    public function groupSummaryByCode(string $localeCode, ?string $code = null): ?object
    {
        $group = $code ? $this->groupSummaryQuery()->where('attribute_groups.code', $code)->first() : null;

        $group ??= $this->groupSummaryQuery()->first();

        return $this->withGroupName($group, $localeCode);
    }

    /**
     * Get one of this family's groups by id.
     */
    public function groupSummaryById(string $localeCode, int $groupId): ?object
    {
        return $this->withGroupName(
            $this->groupSummaryQuery()->where('attribute_groups.id', $groupId)->first(),
            $localeCode
        );
    }

    /**
     * Get the group that follows the given display position, so the editor can
     * walk the family one group at a time without holding the whole list.
     */
    public function groupSummaryAfter(string $localeCode, int $position): ?object
    {
        return $this->withGroupName(
            $this->groupSummaryQuery()
                ->where('attribute_family_group_mappings.position', '>', $position)
                ->first(),
            $localeCode
        );
    }

    /**
     * Base query for this family's group summaries, in display order.
     */
    protected function groupSummaryQuery(): Builder
    {
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
            ]);
    }

    /**
     * Resolve a group summary's translated name in the requested locale.
     */
    protected function withGroupName(?object $group, string $localeCode): ?object
    {
        if (! $group) {
            return null;
        }

        $names = $this->translatedNames(
            'attribute_group_translations',
            'attribute_group_id',
            $localeCode,
            fn ($query) => $query->where('attribute_group_translations.attribute_group_id', $group->id)
        );

        $group->name = $names[$group->id] ?? null;

        return $group;
    }

    /**
     * Get one translated name per row, keyed by owner id, preferring the
     * requested locale over the fallback.
     *
     * Kept as its own query rather than two aliased self-joins, which break
     * under a configured table prefix: Laravel prefixes join aliases too.
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
     * Get one page of this family's group mappings, in display order.
     *
     * Matching on a search term needs the translated names up front, so they are
     * resolved before the page is cut rather than per row.
     *
     * @return array{groups: Collection<int, object>, total: int, lastPage: int}
     */
    public function paginateGroupSummaries(string $localeCode, int $page, int $perPage, string $search = ''): array
    {
        $groups = $this->groupSummaries($localeCode);

        if ($search !== '') {
            $term = mb_strtolower($search);

            $groups = $groups->filter(fn ($group): bool => str_contains(mb_strtolower((string) $group->name), $term)
                || str_contains(mb_strtolower($group->code), $term)
            )->values();
        }

        $total = $groups->count();

        return [
            'groups'   => $groups->forPage($page, $perPage)->values(),
            'total'    => $total,
            'lastPage' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    /**
     * Get label-only rows for this family's group mappings, in display order.
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
