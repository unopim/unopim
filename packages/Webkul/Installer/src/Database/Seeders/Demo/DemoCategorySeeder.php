<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Seeds the demo category fields and the demo tree.
 *
 * Nested-set bounds are computed here rather than carried in the dataset, so
 * editing the tree never leaves the `_lft` / `_rgt` pairs inconsistent.
 */
class DemoCategorySeeder extends Seeder
{
    use LoadsDemoData;

    public function run(): void
    {
        $data = $this->demoData('categories');

        DB::transaction(function () use ($data): void {
            $this->seedFields($data['fields']);

            $this->seedTree($data['tree']);
        });

        DatabaseSequenceHelper::fixSequences(['categories', 'category_fields', 'category_field_options']);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     */
    protected function seedFields(array $fields): void
    {
        $position = (int) DB::table('category_fields')->max('position') + 1;

        $now = Date::now();

        foreach ($fields as $field) {
            DB::table('category_fields')->updateOrInsert(
                ['code' => $field['code']],
                [
                    'type'             => $field['type'],
                    'validation'       => $field['validation'] ?? null,
                    'position'         => $position++,
                    'is_required'      => $field['is_required'] ?? false,
                    'is_unique'        => false,
                    'status'           => true,
                    'section'          => $field['section'] ?? 'left',
                    'value_per_locale' => $field['value_per_locale'] ?? false,
                    'enable_wysiwyg'   => $field['enable_wysiwyg'] ?? false,
                    'regex_pattern'    => null,
                    'updated_at'       => $now,
                    'created_at'       => $now,
                ]
            );

            $fieldId = (int) DB::table('category_fields')->where('code', $field['code'])->value('id');

            foreach ($field['labels'] as $locale => $name) {
                DB::table('category_field_translations')->updateOrInsert(
                    ['category_field_id' => $fieldId, 'locale' => $locale],
                    ['name' => $name]
                );
            }

            $this->seedFieldOptions($fieldId, $field['options'] ?? []);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $options
     */
    protected function seedFieldOptions(int $fieldId, array $options): void
    {
        foreach (array_values($options) as $index => $option) {
            DB::table('category_field_options')->updateOrInsert(
                ['category_field_id' => $fieldId, 'code' => $option['code']],
                ['sort_order' => $index + 1]
            );

            $optionId = (int) DB::table('category_field_options')
                ->where('category_field_id', $fieldId)
                ->where('code', $option['code'])
                ->value('id');

            foreach ($option['labels'] as $locale => $label) {
                DB::table('category_field_option_translations')->updateOrInsert(
                    ['category_field_option_id' => $optionId, 'locale' => $locale],
                    ['label' => $label]
                );
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    protected function seedTree(array $tree): void
    {
        $root = DB::table('categories')->where('code', 'root')->first();

        if (! $root) {
            return;
        }

        DB::table('categories')->whereNotNull('parent_id')->delete();

        $now = Date::now();

        $childrenOf = [];

        foreach ($tree as $node) {
            $childrenOf[$node['parent'] ?? 'root'][] = $node;
        }

        $bounds = $this->computeBounds($childrenOf);

        $idOf = ['root' => (int) $root->id];

        foreach ($tree as $node) {
            $parentId = $idOf[$node['parent']] ?? (int) $root->id;

            $id = DB::table('categories')->insertGetId([
                'code'            => $node['code'],
                'parent_id'       => $parentId,
                '_lft'            => $bounds[$node['code']]['lft'],
                '_rgt'            => $bounds[$node['code']]['rgt'],
                'additional_data' => json_encode($this->additionalData($node), JSON_UNESCAPED_UNICODE),
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);

            $idOf[$node['code']] = (int) $id;
        }

        DB::table('categories')->where('id', $root->id)->update([
            '_lft'       => 1,
            '_rgt'       => $bounds['root']['rgt'],
            'updated_at' => $now,
        ]);
    }

    /**
     * Depth-first nested-set numbering over the parent → children index.
     *
     * @param  array<string, array<int, array<string, mixed>>>  $childrenOf
     * @return array<string, array{lft: int, rgt: int}>
     */
    protected function computeBounds(array $childrenOf): array
    {
        $bounds = [];
        $counter = 0;

        $walk = function (string $code) use (&$walk, &$bounds, &$counter, $childrenOf): void {
            $lft = ++$counter;

            foreach ($childrenOf[$code] ?? [] as $child) {
                $walk($child['code']);
            }

            $bounds[$code] = ['lft' => $lft, 'rgt' => ++$counter];
        };

        $walk('root');

        return $bounds;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    protected function additionalData(array $node): array
    {
        $data = ['common' => $node['common'] ?? []];

        foreach ($node['names'] as $locale => $name) {
            $data['locale_specific'][$locale] = array_merge(
                [
                    'name'        => $name,
                    'description' => $node['descriptions'][$locale] ?? '',
                ],
                $node['locale_extra'][$locale] ?? []
            );
        }

        return $data;
    }
}
