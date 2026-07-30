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
     */
    public function boot(): void
    {
        $this->ensureFixtureTable();

        /*
         * Under `--parallel`, boot runs against the main database before the
         * framework switches the connection to the per-worker database — so the
         * DDL above lands in the wrong schema. Re-run it once the switch has
         * happened. setUpTestCase callbacks fire in registration order, and this
         * provider boots before the framework's ParallelTestingServiceProvider —
         * registering from `booted()` queues the callback after the framework's
         * database swap, and it still runs before DatabaseTransactions opens its
         * per-test transaction.
         */
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
        if (! Schema::hasTable('wk_resource_kit_items')) {
            (require __DIR__.'/migrations/2026_07_15_000001_create_resource_kit_items_table.php')->up();
        }

        if (! Schema::hasColumn('wk_resource_kit_items', 'label')) {
            Schema::table('wk_resource_kit_items', function ($table) {
                $table->string('label')->nullable();
            });
        }
    }
}
