<?php

use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

/*
 * Contract + performance-regression coverage for the async option lookup
 * (AjaxOptionsController::getOptions) that powers every searchable multiselect.
 * Labels must resolve through the translation fallback, and formatting a page must
 * stay a small, constant number of queries — never one lazy translation load per row.
 */

it('returns translated attribute options with the paginated envelope', function () {
    $this->loginAsAdmin();

    $response = get(
        route('admin.catalog.options.fetch-all', ['entityName' => 'attributes']),
        ['Accept' => 'application/json']
    )->assertOk();

    $options = $response->json('options');

    expect($options)->toBeArray()->not->toBeEmpty()
        ->and($response->json('total'))->toBeGreaterThan(0)
        ->and($response->json('page'))->toBe(1)
        ->and($response->json('lastPage'))->toBeGreaterThanOrEqual(1);

    foreach ($options as $option) {
        expect($option)->toHaveKeys(['id', 'code', 'label'])
            ->and($option['label'])->toBeString()->not->toBe('');
    }
});

it('formats an options page without an N+1 translation load', function () {
    $this->loginAsAdmin();

    $countFor = function (int $perPage): int {
        $queries = 0;
        $listener = function () use (&$queries): void {
            $queries++;
        };

        DB::listen($listener);

        get(
            route('admin.catalog.options.fetch-all', ['entityName' => 'attributes', 'perPage' => $perPage]),
            ['Accept' => 'application/json']
        )->assertOk();

        return $queries;
    };

    // Eager loading makes the query count independent of page size. Under the old
    // lazy-per-row behaviour a 40-row page would fire ~35 more queries than a 5-row page.
    expect($countFor(40) - $countFor(5))->toBeLessThanOrEqual(2);
});
