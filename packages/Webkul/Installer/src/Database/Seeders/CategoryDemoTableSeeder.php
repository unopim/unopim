<?php

namespace Webkul\Installer\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JsonException;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

/**
 * Seeds the demo category tree from
 * `Installer/src/Database/Data/categories.json`.
 *
 * The JSON contains every category needed by the demo products
 * (matching the dump 1:1). Root is updated in place; all other rows
 * are deleted and re-inserted so the seeder is idempotent.
 */
class CategoryDemoTableSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->loadCategoryData();

        if ($categories === null) {
            return;
        }

        $root = DB::table('categories')->where('code', 'root')->first();

        if (! $root) {
            $this->command?->warn('Root category not found — skipping demo category seeding.');

            return;
        }

        $now = Carbon::now();

        DB::transaction(function () use ($root, $now, $categories): void {
            DB::table('categories')->whereNotNull('parent_id')->delete();

            $codeToId = ['root' => (int) $root->id];

            foreach ($categories as $category) {
                if ($category['code'] === 'root') {
                    // Preserve the root category's additional_data untouched so the
                    // admin UI keeps its default `[root]` label (used by fillChannelForm
                    // in E2E tests and matches the core installer's bare root setup).
                    DB::table('categories')->where('id', $root->id)->update([
                        '_lft'       => (int) $category['_lft'],
                        '_rgt'       => (int) $category['_rgt'],
                        'updated_at' => $now,
                    ]);

                    continue;
                }

                $parentId = $codeToId[$category['parent_code']] ?? $root->id;

                DB::table('categories')->insert([
                    'code'            => $category['code'],
                    'parent_id'       => $parentId,
                    '_lft'            => (int) $category['_lft'],
                    '_rgt'            => (int) $category['_rgt'],
                    'additional_data' => json_encode($category['additional_data'], JSON_UNESCAPED_UNICODE),
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);

                $codeToId[$category['code']] = (int) DB::table('categories')
                    ->where('code', $category['code'])
                    ->value('id');
            }
        });

        DatabaseSequenceHelper::fixSequences(['categories']);

        $this->command?->info('Demo categories seeded successfully.');
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    private function loadCategoryData(): ?array
    {
        $jsonPath = __DIR__.'/../Data/categories.json';

        if (! File::exists($jsonPath)) {
            $this->command?->error('categories.json file not found.');

            return null;
        }

        try {
            $decoded = json_decode(
                File::get($jsonPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->command?->error('Failed to parse categories.json: '.$e->getMessage());

            return null;
        }

        if (! isset($decoded['categories']) || ! is_array($decoded['categories'])) {
            $this->command?->error('Invalid JSON format: missing "categories" key.');

            return null;
        }

        return $decoded['categories'];
    }
}
