<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Seeds the demo attribute families, their group mappings and the
 * completeness settings each family is scored against.
 */
class DemoFamilySeeder extends Seeder
{
    use LoadsDemoData;

    public function __construct(
        protected DemoProductSeeder $catalog,
    ) {}

    public function run(): void
    {
        $data = $this->demoData('families');

        DB::transaction(function () use ($data): void {
            $attributeIds = DB::table('attributes')->pluck('id', 'code');
            $groupIds = DB::table('attribute_groups')->pluck('id', 'code');
            $channelIds = DB::table('channels')
                ->whereIn('code', $data['completeness_channels'])
                ->pluck('id', 'code');

            $axes = $this->axesByFamily();

            foreach ($data['families'] as $family) {
                $familyId = $this->seedFamily($family);

                $groups = $this->withVariantAxes($family['groups'], $axes[$family['code']] ?? []);

                $this->seedGroupMappings($familyId, $groups, $groupIds, $attributeIds);

                $this->seedCompleteness($familyId, $family['completeness'], $attributeIds, $channelIds);
            }
        });
    }

    /**
     * Variant axis codes each family's configurables are built from.
     *
     * @return array<string, array<int, string>>
     */
    protected function axesByFamily(): array
    {
        $axes = [];

        foreach ($this->catalog->catalog() as $product) {
            foreach ($product['axes'] ?? [] as $axis) {
                $axes[$product['family']][] = $axis;
            }
        }

        return array_map(array_unique(...), $axes);
    }

    /**
     * A variant axis the family does not carry leaves the configurable
     * unbuildable in the admin while its value still renders on the product,
     * so any axis missing from the dataset's own groups is added here.
     *
     * @param  array<string, array<int, string>>  $groups
     * @param  array<int, string>  $axes
     * @return array<string, array<int, string>>
     */
    protected function withVariantAxes(array $groups, array $axes): array
    {
        $carried = array_merge(...array_values($groups));

        foreach ($axes as $axis) {
            if (! in_array($axis, $carried, true)) {
                $groups['general'][] = $axis;
            }
        }

        return $groups;
    }

    /**
     * @param  array<string, mixed>  $family
     */
    protected function seedFamily(array $family): int
    {
        DB::table('attribute_families')->updateOrInsert(
            ['code' => $family['code']],
            ['status' => true]
        );

        $familyId = (int) DB::table('attribute_families')->where('code', $family['code'])->value('id');

        foreach ($family['labels'] as $locale => $name) {
            DB::table('attribute_family_translations')->updateOrInsert(
                ['attribute_family_id' => $familyId, 'locale' => $locale],
                ['name' => $name]
            );
        }

        return $familyId;
    }

    /**
     * @param  array<string, array<int, string>>  $groups
     * @param  Collection<string, int>  $groupIds
     * @param  Collection<string, int>  $attributeIds
     */
    protected function seedGroupMappings(int $familyId, array $groups, $groupIds, $attributeIds): void
    {
        $existingGroupIds = DB::table('attribute_family_group_mappings')
            ->where('attribute_family_id', $familyId)
            ->pluck('id');

        DB::table('attribute_group_mappings')->whereIn('attribute_family_group_id', $existingGroupIds)->delete();
        DB::table('attribute_family_group_mappings')->where('attribute_family_id', $familyId)->delete();

        $position = 1;

        foreach ($groups as $groupCode => $attributeCodes) {
            $groupId = $groupIds[$groupCode] ?? null;

            if (! $groupId) {
                continue;
            }

            $mappingId = DB::table('attribute_family_group_mappings')->insertGetId([
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupId,
                'position'            => $position++,
            ]);

            $rows = [];
            $attributePosition = 1;

            foreach ($attributeCodes as $code) {
                if (! isset($attributeIds[$code])) {
                    continue;
                }

                $rows[] = [
                    'attribute_id'              => (int) $attributeIds[$code],
                    'attribute_family_group_id' => $mappingId,
                    'position'                  => $attributePosition++,
                ];
            }

            if ($rows !== []) {
                DB::table('attribute_group_mappings')->insert($rows);
            }
        }
    }

    /**
     * @param  array<int, string>  $attributeCodes
     * @param  Collection<string, int>  $attributeIds
     * @param  Collection<string, int>  $channelIds
     */
    protected function seedCompleteness(int $familyId, array $attributeCodes, $attributeIds, $channelIds): void
    {
        $now = Date::now();

        DB::table('completeness_settings')->where('family_id', $familyId)->delete();

        $rows = [];

        foreach ($channelIds as $channelId) {
            foreach ($attributeCodes as $code) {
                if (! isset($attributeIds[$code])) {
                    continue;
                }

                $rows[] = [
                    'family_id'    => $familyId,
                    'attribute_id' => (int) $attributeIds[$code],
                    'channel_id'   => (int) $channelId,
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ];
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('completeness_settings')->insert($chunk);
        }
    }
}
