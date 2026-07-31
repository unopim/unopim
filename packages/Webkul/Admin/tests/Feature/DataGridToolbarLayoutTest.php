<?php

use function Pest\Laravel\get;

it('renders the datagrid toolbar so it wraps instead of widening the page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    $toolbar = [];

    preg_match('/class="datagrid-toolbar[^"]*"/', $response->getContent(), $toolbar);

    expect($toolbar)->not->toBeEmpty()
        ->and($toolbar[0])->toContain('flex-wrap')
        ->and($toolbar[0])->not->toContain('max-md:flex-wrap');
});

it('renders the main content area guarded against horizontal page scroll', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.products.index'))->assertOk();

    $main = [];

    preg_match('/<main id="main-content"[^>]*class="[^"]*"/', $response->getContent(), $main);

    expect($main)->not->toBeEmpty()
        ->and($main[0])->toContain('overflow-x-hidden');
});
