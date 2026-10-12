<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;

use function Pest\Laravel\get;

/*
 * The "based on" picker on the families index used to hydrate every family and read the
 * translated `name` accessor, costing one extra query per family. These tests pin the
 * page's query count to a constant so that regression cannot come back unnoticed.
 */

function familiesIndexQueryCount(): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    get(route('admin.catalog.families.index'))->assertOk();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}

function makeFamilies(int $count, string $prefix): void
{
    AttributeFamily::factory()
        ->count($count)
        ->sequence(fn ($sequence): array => ['code' => $prefix.'_'.$sequence->index])
        ->create();
}

it('does not run more queries as families are added', function () {
    $this->loginAsAdmin();

    makeFamilies(2, 'qc_baseline');

    // First render warms the config/locale caches, so it is not a comparable baseline.
    familiesIndexQueryCount();

    $baseline = familiesIndexQueryCount();

    makeFamilies(20, 'qc_extra');

    expect(familiesIndexQueryCount())->toBe($baseline);
});

it('labels a family without a translation by its code', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $family->translations()->delete();

    get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertSee('['.$family->code.']');
});
