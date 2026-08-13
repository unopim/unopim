<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\DemoAttributeSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCoreSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoFamilySeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoWorkspaceSeeder;

/**
 * Every saved view the demo ships has to survive being applied. A filter
 * payload without an operator falls back to `in`, which the typed filters
 * behind the product grid reject for anything but a list.
 */
it('applies every seeded product grid view without a filter error', function () {
    $this->loginAsAdmin();

    resolve(DemoCoreSeeder::class)->run();
    resolve(DemoAttributeSeeder::class)->run();
    resolve(DemoFamilySeeder::class)->run();
    resolve(DemoWorkspaceSeeder::class)->run();

    $views = DB::table('product_grid_views')->pluck('payload', 'name');

    expect($views)->not->toBeEmpty();

    foreach ($views as $name => $payload) {
        $filters = json_decode($payload, true, 512, JSON_THROW_ON_ERROR)['filters'] ?? [];

        $query = [];

        foreach ($filters as $filter) {
            $query[$filter['index']] = $filter['value'];
        }

        $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->json('GET', route('admin.catalog.products.index'), ['filters' => $query])
            ->assertOk("saved view \"$name\" could not be applied");
    }
});
