<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Illuminate\Database\Query\Builder;
use Webkul\AdminApi\ApiDataSource;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Repositories\VariantStructureRepository;
use Webkul\Product\Services\VariantStructurePlanner;

/**
 * Serves the variant structures of one attribute family.
 *
 * The family is a hard scope, not a client filter: the source is always bound to a
 * family and registers no field filters, so `filters` cannot widen past it. List
 * responses skip StructureCache, whose key would collide across families.
 */
class VariantStructureDataSource extends ApiDataSource
{
    protected AttributeFamily $attributeFamily;

    /**
     * The family's attribute codes, resolved once however many structures the
     * response carries.
     *
     * @var array<int, string>|null
     */
    protected ?array $familyAttributeCodes = null;

    /**
     * Create a new DataSource instance.
     *
     * @return void
     */
    public function __construct(
        protected VariantStructureRepository $variantStructureRepository,
        protected VariantStructurePlanner $variantStructurePlanner,
    ) {}

    /**
     * Bind the data source to the family whose structures it serves.
     */
    public function forFamily(AttributeFamily $attributeFamily): static
    {
        $this->attributeFamily = $attributeFamily;

        return $this;
    }

    /**
     * Prepares the query builder for API requests.
     *
     * @return Builder The query builder for the variant structure repository.
     */
    public function prepareApiQueryBuilder()
    {
        return $this->variantStructureRepository->queryBuilder();
    }

    /**
     * Restricts every listing to the bound family.
     */
    public function setDefaultFilters($queryBuilder)
    {
        $queryBuilder->where('variant_structures.attribute_family_id', $this->attributeFamily->id);

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Format data for API response.
     *
     * @return array An array of formatted variant structure data.
     */
    public function formatData(): array
    {
        return array_map(
            fn (VariantStructure $structure): array => $this->normalize($structure),
            $this->paginator->items()
        );
    }

    /**
     * Build the API representation of a single variant structure.
     *
     * @return array<string, mixed>
     */
    public function normalize(VariantStructure $structure): array
    {
        return [
            'code'                 => $structure->code,
            'name'                 => $structure->name,
            'family'               => $this->attributeFamily->code,
            'levels'               => (int) $structure->levels,
            'axes'                 => $this->groupAxes($structure),
            'placements'           => $this->groupPlacements($structure),
            'effective_placements' => $this->effectivePlacements($structure),
            'created_at'           => $structure->created_at?->toJSON(),
            'updated_at'           => $structure->updated_at?->toJSON(),
        ];
    }

    /**
     * Group axis attribute codes by level, ordered by the row's position.
     *
     * Both levels are always emitted, empty when unused, so a client never has
     * to branch on a key being absent. An empty `level_2` is also a legal PUT
     * body for a single-level structure, so a GET result round-trips as-is.
     *
     * @return array<string, array<int, string>>
     */
    protected function groupAxes(VariantStructure $structure): array
    {
        $grouped = [
            'level_1' => [],
            'level_2' => [],
        ];

        foreach ($structure->axes as $axis) {
            $code = $axis->attribute?->code;

            if ($code === null || ! isset($grouped[$axis->level])) {
                continue;
            }

            $grouped[$axis->level][(int) $axis->position] = $code;
        }

        foreach ($grouped as $level => $codes) {
            ksort($codes);

            $grouped[$level] = array_values($codes);
        }

        return $grouped;
    }

    /**
     * Group explicitly placed attribute codes by level.
     *
     * Only the rows the structure actually stores, which are the deviations
     * from the `common` default; {@see effectivePlacements()} reports the
     * resulting ownership of every attribute.
     *
     * @return array<string, array<int, string>>
     */
    protected function groupPlacements(VariantStructure $structure): array
    {
        $grouped = [
            'common'     => [],
            'sub_parent' => [],
            'variant'    => [],
        ];

        foreach ($structure->placements as $placement) {
            $code = $placement->attribute?->code;

            if ($code === null || ! isset($grouped[$placement->level])) {
                continue;
            }

            $grouped[$placement->level][] = $code;
        }

        foreach ($grouped as $level => $codes) {
            sort($codes);

            $grouped[$level] = $codes;
        }

        return $grouped;
    }

    /**
     * Every attribute of the family under the level that actually governs it.
     *
     * Read-only: a write request carrying this block has it ignored rather than
     * rejected, so a GET body can be edited and PUT straight back. Levels come from
     * VariantStructurePlanner, the same map the product write guard enforces.
     *
     * @return array<string, array<int, string>>
     */
    protected function effectivePlacements(VariantStructure $structure): array
    {
        return $this->variantStructurePlanner->effectivePlacements(
            $structure,
            $this->familyAttributeCodes()
        );
    }

    /**
     * The family's attribute codes in family order, each listed once. Both position
     * columns tie freely, and neither MySQL nor PostgreSQL orders a tie predictably,
     * so the attribute id settles it and keeps effective_placements stable.
     *
     * @return array<int, string>
     */
    protected function familyAttributeCodes(): array
    {
        return $this->familyAttributeCodes ??= array_values(array_unique(
            $this->attributeFamily->customAttributes()
                ->orderBy('attribute_family_group_mappings.position')
                ->orderBy('attribute_group_mappings.position')
                ->orderBy('attributes.id')
                ->pluck('attributes.code')
                ->all()
        ));
    }
}
