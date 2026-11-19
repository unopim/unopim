<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Category\Models\Category;

uses(DatabaseTransactions::class);

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    $this->loginAsAdmin();
});

function gridRecordByCode(string $routeName, string $code, string $codeKey = 'code'): ?array
{
    $response = test()->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route($routeName, [
            'locale'  => 'en_US',
            'filters' => [$codeKey => [$code]],
        ]));

    $response->assertOk();

    return collect($response->json('records'))->firstWhere('code', $code);
}

dataset('labels', [
    'digit after <'  => ['Washer <5mm thick', 'Washer <5mm thick'],
    'number after <' => ['Bolt <10', 'Bolt <10'],
    'letter after <' => ['Rated <abc grade', 'Rated <abc grade'],
    'space after <'  => ['a < b', 'a < b'],
    'formatted text' => ['<b>Bold</b> label', 'Bold label'],
    'script payload' => ['<img src=x onerror=alert(1)>Label', 'Label'],
]);

it('shows a category name that contains a less-than sign in the category grid', function (string $stored, string $shown) {
    $code = 'cat_'.Str::lower(Str::random(8));

    Category::factory()->create([
        'parent_id'       => null,
        'code'            => $code,
        'additional_data' => ['locale_specific' => ['en_US' => ['name' => $stored]]],
    ]);

    $record = gridRecordByCode('admin.catalog.categories.index', $code);

    expect($record)->not->toBeNull()
        ->and($record['category_name'])->toBe($shown)
        ->and($record['display_name'])->toBe($shown);
})->with('labels');

it('shows an attribute label that contains a less-than sign in the attribute grid', function (string $stored, string $shown) {
    $attribute = Attribute::factory()->create(['code' => 'attr_'.Str::lower(Str::random(8)), 'type' => 'text']);

    $attribute->translateOrNew('en_US')->name = $stored;
    $attribute->save();

    $record = gridRecordByCode('admin.catalog.attributes.index', $attribute->code);

    expect($record)->not->toBeNull()
        ->and($record['name'])->toBe($shown);
})->with('labels');

it('shows an attribute family label that contains a less-than sign in the family grid', function (string $stored, string $shown) {
    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::lower(Str::random(8))]);

    $family->translateOrNew('en_US')->name = $stored;
    $family->save();

    $record = gridRecordByCode('admin.catalog.families.index', $family->code);

    expect($record)->not->toBeNull()
        ->and($record['name'])->toBe($shown);
})->with('labels');

it('shows an attribute group label that contains a less-than sign in the attribute group grid', function (string $stored, string $shown) {
    $group = AttributeGroup::factory()->create(['code' => 'grp_'.Str::lower(Str::random(8))]);

    $group->translateOrNew('en_US')->name = $stored;
    $group->save();

    $record = gridRecordByCode('admin.catalog.attribute.groups.index', $group->code);

    expect($record)->not->toBeNull()
        ->and($record['name'])->toBe($shown);
})->with('labels');
