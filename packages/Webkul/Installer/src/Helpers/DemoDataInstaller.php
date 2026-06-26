<?php

namespace Webkul\Installer\Helpers;

use Closure;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Completeness\Console\RecalculateCompletenessCommand;
use Webkul\ElasticSearch\Console\Command\CategoryIndexer;
use Webkul\ElasticSearch\Console\Command\ProductIndexer;
use Webkul\Installer\Database\Seeders\CategoryDemoTableSeeder;
use Webkul\Installer\Database\Seeders\DemoExtrasTableSeeder;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;

/**
 * Runs the demo extras, demo categories, and sample products seeders
 * plus Elasticsearch reindexing and completeness recalculation.
 *
 * Shared by the CLI installer, the UI installer endpoint, and the
 * `unopim:install:demo-data` command used by docker-compose so all
 * three install paths emit identical seeded data.
 */
class DemoDataInstaller
{
    /**
     * Run every demo seeder synchronously.
     *
     * The optional reporter closure receives short status strings so
     * callers (artisan commands, controllers, tests) can surface them.
     *
     * Idempotent: when demo data is already present (a non-root
     * category exists, which the base installer never creates), the
     * call short-circuits with `skipped: true`. Pass `$force: true`
     * to re-run the seeders even when data is already present.
     *
     * @return array{success: bool, skipped?: bool, error?: string}
     */
    public function seed(?Closure $reporter = null, bool $force = false): array
    {
        $report = $reporter ?? static fn (string $message) => null;

        if (! $force && $this->isAlreadySeeded()) {
            $report('Demo data is already seeded; skipping. Pass --force to re-seed.');

            return ['success' => true, 'skipped' => true];
        }

        try {
            $report('Seeding demo extras (channels, attributes, families, core config, ...)...');
            app(DemoExtrasTableSeeder::class)->run();

            $report('Seeding demo categories...');
            app(CategoryDemoTableSeeder::class)->run();

            $report('Seeding sample products...');
            app(ProductTableSeeder::class)->run();

            if (config('elasticsearch.enabled') == 'true') {
                try {
                    // These indexers are only auto-registered in the console, so
                    // register them for the web installer. Indexing is an
                    // optimization — never fail the whole seed if it errors
                    // (e.g. Elasticsearch unreachable); the data is already in
                    // the database and can be re-indexed later.
                    Artisan::registerCommand(app(CategoryIndexer::class));
                    Artisan::registerCommand(app(ProductIndexer::class));

                    $report('Re-indexing categories to Elasticsearch...');
                    Artisan::call('unopim:category:index');

                    $report('Re-indexing products to Elasticsearch...');
                    Artisan::call('unopim:product:index');
                } catch (Throwable $e) {
                    $report('Elasticsearch re-indexing skipped: '.$e->getMessage());
                }
            }

            $report('Recalculating product completeness...');
            $this->recalculateCompleteness();

            // Sanity check: a "default" attribute family with zero group
            // mappings would render the catalog unusable (products can't
            // be created against an empty family). Surface this loudly
            // rather than leaving the install silently broken.
            if (! $this->defaultFamilyHasGroups()) {
                return [
                    'success' => false,
                    'error'   => 'Demo seeding completed but the default attribute family has no group mappings — refusing to leave the catalog in an unusable state.',
                ];
            }

            return ['success' => true];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Returns true when the `default` attribute family has at least one
     * attribute-group mapping. Defensive guard against partial seeds
     * leaving the catalog without a usable default family.
     */
    public function defaultFamilyHasGroups(): bool
    {
        try {
            $familyId = DB::table('attribute_families')->where('code', 'default')->value('id');

            if (! $familyId) {
                return false;
            }

            return DB::table('attribute_family_group_mappings')
                ->where('attribute_family_id', $familyId)
                ->exists();
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Check if demo or operator-created catalog data already exists.
     */
    public function isAlreadySeeded(): bool
    {
        try {
            return DB::table('products')->exists()
                || DB::table('categories')->whereNotNull('parent_id')->exists()
                || DB::table('attribute_families')->where('code', '!=', 'default')->exists()
                || DB::table('channels')->where('code', '!=', 'default')->exists();
        } catch (Throwable) {
            // Table missing / DB not migrated yet → treat as not seeded
            // so the caller can decide how to handle the failure mode.
            return false;
        }
    }

    /**
     * Recalculate product completeness synchronously.
     */
    protected function recalculateCompleteness(): void
    {
        $originalDefault = config('queue.default');

        try {
            config(['queue.default' => 'sync']);

            // The Completeness package only auto-registers this command when
            // running in the console, so register it explicitly for the web
            // installer (which calls this inside an HTTP request).
            Artisan::registerCommand(app(RecalculateCompletenessCommand::class));

            Artisan::call('unopim:completeness:recalculate', ['--all' => true]);
        } finally {
            config(['queue.default' => $originalDefault]);
        }
    }
}
