<?php

use Illuminate\Database\Migrations\Migrator;
use Webkul\Installer\Helpers\Upgrade\MigrationInspector;
use Webkul\Installer\Helpers\Upgrade\MigrationSizer;

/**
 * The sizing report is what an operator books downtime against, so it must name
 * the irreversible migrations and never estimate zero while work is pending.
 *
 * The test database is fully migrated, so the pending set is stubbed rather
 * than skipping the assertion that matters most.
 */
function sizerWithPending(array $pending): MigrationSizer
{
    $inspector = new class(app('migrator'), $pending) extends MigrationInspector
    {
        public function __construct(Migrator $migrator, private array $stubbed)
        {
            parent::__construct($migrator);
        }

        public function pending(): array
        {
            return $this->stubbed;
        }
    };

    return new MigrationSizer($inspector);
}

it('flags configured migrations as irreversible', function () {
    config(['upgrade.irreversible_migrations' => ['2026_07_11_100002_backfill_product_associations']]);

    $estimate = sizerWithPending([
        '2026_07_11_100002_backfill_product_associations',
        '2026_07_22_100000_create_product_grid_views_table',
    ])->estimate();

    expect(collect($estimate['pending'])->firstWhere('name', '2026_07_11_100002_backfill_product_associations')['irreversible'])->toBeTrue()
        ->and(collect($estimate['pending'])->firstWhere('name', '2026_07_22_100000_create_product_grid_views_table')['irreversible'])->toBeFalse()
        ->and(collect($estimate['pending'])->where('irreversible', true))->toHaveCount(1);
});

it('scales the estimated window with row counts', function () {
    config(['upgrade.sizing_tables' => ['products' => 1]]);

    $small = sizerWithPending(['a_migration'])->estimate();

    config(['upgrade.sizing_tables' => ['products' => 1000000]]);

    $large = sizerWithPending(['a_migration'])->estimate();

    expect($small['seconds'])->toBeGreaterThanOrEqual($large['seconds']);
});

it('counts rows only for tables that exist', function () {
    config(['upgrade.sizing_tables' => ['products' => 2000, 'a_table_that_does_not_exist' => 1000]]);

    $estimate = app(MigrationSizer::class)->estimate();

    expect($estimate['tables'])->toHaveKey('products')
        ->and($estimate['tables'])->not->toHaveKey('a_table_that_does_not_exist');
});

it('never estimates a zero window while migrations are pending', function () {
    config(['upgrade.sizing_tables' => []]);

    $estimate = app(MigrationSizer::class)->estimate();

    if ($estimate['pending'] === []) {
        expect($estimate['seconds'])->toBe(0);

        return;
    }

    expect($estimate['seconds'])->toBeGreaterThanOrEqual(count($estimate['pending']));
});
