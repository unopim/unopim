<?php

use Webkul\Admin\DataGrids\Catalog\CategoryDataGrid;

function categoryGridSql(string $locale): string
{
    request()->merge(['locale' => $locale]);

    return app(CategoryDataGrid::class)->prepareQueryBuilder()->toSql();
}

it('does not let a malicious locale inject SQL into the JSON_EXTRACT path', function () {
    $payload = "en_US.name',(SELECT password FROM admins LIMIT 1),'";

    $sql = categoryGridSql($payload);

    expect($sql)->not->toContain('SELECT password');
    expect($sql)->not->toContain('admins');
});

it('does not let a single quote break out of the JSON path literal', function () {
    $sql = categoryGridSql("en_US'");

    // A raw, unescaped single quote inside the JSON path means the string
    // literal was terminated early -> injection point.
    expect($sql)->not->toContain("en_US'");
});

it('preserves a valid locale code in the JSON path', function () {
    $sql = categoryGridSql('en_US');

    expect($sql)->toContain('locale_specific.en_US.name');
});
