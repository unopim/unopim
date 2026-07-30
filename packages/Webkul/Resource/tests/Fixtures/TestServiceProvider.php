<?php

namespace Webkul\Resource\Tests\Fixtures;

use Illuminate\Support\Facades\ParallelTesting;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Webkul\Resource\Routing\Resource;
use Webkul\Resource\Support\ResourceRegistry;

/**
 * Test-only bootstrap for the Resource CRUD kit's Feature tests.
 *
 * Registered early from ResourceTestCase::createApplication() so the
 * one-time fixture table DDL runs before DatabaseTransactions opens its
 * per-test transaction.
 */
class TestServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the fixture resource: migrate its table (once), register it
     * in the ResourceRegistry, and register its admin CRUD routes.
     *
     * Under --parallel, boot still targets the main database, so the DDL is
     * re-run from a booted() callback — queued after the framework's
     * per-worker database swap, before each test's transaction opens.
     */
    public function boot(): void
    {
        $this->ensureFixtureTable();

        $this->app->booted(function (): void {
            ParallelTesting::setUpTestCase(function (): void {
                $this->ensureFixtureTable();
            });
        });

        $this->app->make(ResourceRegistry::class)->register('resource-kit-items', TestResource::class);

        // Mirrors AdminServiceProvider: routes only declare `admin` middleware, `web` is applied by the mounting caller.
        Route::middleware('web')->group(function () {
            Resource::routes('resource-kit-items', TestController::class);
        });
    }

    /**
     * Migrate the fixture table (once) and backfill `label` for test
     * databases created before it was added to the CREATE.
     */
    protected function ensureFixtureTable(): void
    {
        if (! Schema::hasTable('resource_kit_items')) {
            (require __DIR__.'/migrations/2026_07_15_000001_create_resource_kit_items_table.php')->up();
        }

        if (! Schema::hasColumn('resource_kit_items', 'label')) {
            Schema::table('resource_kit_items', function ($table) {
                $table->string('label')->nullable();
            });
        }
    }
}
