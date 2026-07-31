<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Extends the base install's attribute set with the demo attributes.
 *
 * Every attribute type UnoPim ships is represented, including measurement
 * attributes bound to the migration-seeded measurement families, so the demo
 * catalog exercises the whole attribute editor rather than a text-only subset.
 */
class DemoAttributeSeeder extends Seeder
{
    use LoadsDemoData;

    public function run(): void
    {
        $data = $this->demoData('attributes');

        DB::transaction(function () use ($data): void {
            $this->seedGroups($data['groups']);

            foreach ($data['attributes'] as $attribute) {
                $this->seedAttribute($attribute);
            }

            $this->seedExtraOptions($data['extra_options']);

            $this->applyBaseOverrides($data['base_overrides']);

            $this->applyMediaLimits($data['media_limits']);

            $this->promoteMeasurementAttributes($data['measurements']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     */
    protected function seedGroups(array $groups): void
    {
        foreach ($groups as $group) {
            DB::table('attribute_groups')->updateOrInsert(
                ['code' => $group['code']],
                ['is_user_defined' => true]
            );

            $groupId = (int) DB::table('attribute_groups')->where('code', $group['code'])->value('id');

            foreach ($group['labels'] as $locale => $name) {
                DB::table('attribute_group_translations')->updateOrInsert(
                    ['attribute_group_id' => $groupId, 'locale' => $locale],
                    ['name' => $name]
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $attribute
     */
    protected function seedAttribute(array $attribute): void
    {
        $now = Date::now();

        $position = (int) DB::table('attributes')->max('position') + 1;

        DB::table('attributes')->updateOrInsert(
            ['code' => $attribute['code']],
            [
                'type'               => $attribute['type'],
                'validation'         => $attribute['validation'] ?? null,
                'regex_pattern'      => $attribute['regex_pattern'] ?? null,
                'allowed_extensions' => $this->encodeExtensions($attribute['allowed_extensions'] ?? null),
                'max_file_size'      => $attribute['max_file_size'] ?? null,
                'position'           => $position,
                'is_required'        => $attribute['is_required'] ?? false,
                'is_unique'          => $attribute['is_unique'] ?? false,
                'value_per_locale'   => $attribute['value_per_locale'] ?? false,
                'value_per_channel'  => $attribute['value_per_channel'] ?? false,
                'is_filterable'      => $attribute['is_filterable'] ?? false,
                'usable_in_grid'     => $attribute['usable_in_grid'] ?? false,
                'enable_wysiwyg'     => $attribute['enable_wysiwyg'] ?? false,
                'ai_translate'       => $attribute['ai_translate'] ?? false,
                'updated_at'         => $now,
                'created_at'         => $now,
            ]
        );

        $attributeId = (int) DB::table('attributes')->where('code', $attribute['code'])->value('id');

        foreach ($attribute['labels'] as $locale => $name) {
            DB::table('attribute_translations')->updateOrInsert(
                ['attribute_id' => $attributeId, 'locale' => $locale],
                ['name' => $name]
            );
        }

        $this->seedOptions($attributeId, $attribute['options'] ?? []);

        if (isset($attribute['measurement'])) {
            $this->linkMeasurement($attributeId, $attribute['measurement']);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function seedOptions(int $attributeId, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            $optionId = $this->matchingOptionId($attributeId, $option['code']);

            $payload = [
                'code'         => $option['code'],
                'sort_order'   => $index + 1,
                'swatch_value' => $option['swatch_value'] ?? null,
            ];

            if ($optionId === null) {
                $optionId = (int) DB::table('attribute_options')->insertGetId(
                    $payload + ['attribute_id' => $attributeId]
                );
            } else {
                DB::table('attribute_options')->where('id', $optionId)->update($payload);
            }

            foreach ($option['labels'] as $locale => $label) {
                DB::table('attribute_option_translations')->updateOrInsert(
                    ['attribute_option_id' => $optionId, 'locale' => $locale],
                    ['label' => $label]
                );
            }
        }
    }

    /**
     * Existing option whose code matches case-insensitively.
     *
     * The unique index on (attribute_id, code) is case-insensitive under the
     * default MySQL collation, so seeding `s` next to the base installer's `S`
     * silently rewrites that row instead of adding one. Product values are
     * matched case-sensitively in the UI, so the mismatch would leave the
     * variant's axis select empty and the row unsavable.
     */
    protected function matchingOptionId(int $attributeId, string $code): ?int
    {
        $id = DB::table('attribute_options')
            ->where('attribute_id', $attributeId)
            ->whereRaw('lower(code) = ?', [mb_strtolower($code)])
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $extraOptions
     */
    protected function seedExtraOptions(array $extraOptions): void
    {
        foreach ($extraOptions as $attributeCode => $options) {
            $attributeId = (int) DB::table('attributes')->where('code', $attributeCode)->value('id');

            if ($attributeId === 0) {
                continue;
            }

            $this->seedOptions($attributeId, $options);
        }
    }

    /**
     * Re-flag the attributes the base installer created. Only the listed flags
     * are touched, so an attribute the operator already tuned keeps everything
     * the demo does not have an opinion about.
     *
     * @param  array<string, array<string, bool>>  $overrides
     */
    protected function applyBaseOverrides(array $overrides): void
    {
        foreach ($overrides as $code => $flags) {
            DB::table('attributes')->where('code', $code)->update($flags);
        }
    }

    /**
     * @param  array<string, array{allowed_extensions: array<int, string>, max_file_size: int}>  $limits
     */
    protected function applyMediaLimits(array $limits): void
    {
        foreach ($limits as $code => $limit) {
            DB::table('attributes')->where('code', $code)->update([
                'allowed_extensions' => $this->encodeExtensions($limit['allowed_extensions']),
                'max_file_size'      => $limit['max_file_size'],
            ]);
        }
    }

    /**
     * `allowed_extensions` is a JSON column the model casts to an array.
     *
     * @param  array<int, string>|null  $extensions
     */
    protected function encodeExtensions(?array $extensions): ?string
    {
        return $extensions === null ? null : json_encode($extensions, JSON_THROW_ON_ERROR);
    }

    /**
     * Convert the base install's dimension attributes into measurement
     * attributes so the measurement families installed by migration are
     * actually reachable from a product.
     *
     * @param  array<string, array{family: string, unit: string}>  $measurements
     */
    protected function promoteMeasurementAttributes(array $measurements): void
    {
        foreach ($measurements as $code => $measurement) {
            $attributeId = (int) DB::table('attributes')->where('code', $code)->value('id');

            if ($attributeId === 0) {
                continue;
            }

            DB::table('attributes')->where('id', $attributeId)->update(['type' => 'measurement']);

            $this->linkMeasurement($attributeId, $measurement);
        }
    }

    /**
     * @param  array{family: string, unit: string}  $measurement
     */
    protected function linkMeasurement(int $attributeId, array $measurement): void
    {
        $now = Date::now();

        DB::table('attribute_measurement')->updateOrInsert(
            ['attribute_id' => $attributeId],
            [
                'family_code' => $measurement['family'],
                'unit_code'   => $measurement['unit'],
                'updated_at'  => $now,
                'created_at'  => $now,
            ]
        );
    }
}
