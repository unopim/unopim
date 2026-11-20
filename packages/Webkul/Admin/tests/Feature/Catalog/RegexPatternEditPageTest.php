<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;

use function Pest\Laravel\get;

const BREAKOUT_REGEX_PATTERN = 'x"><span>{{ constructor.constructor(\'alert(document.domain)\')() }}</span>';

dataset('regex pattern edit pages', [
    'attribute' => [
        fn (): string => route('admin.catalog.attributes.edit', [
            'id' => Attribute::factory()->create(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => BREAKOUT_REGEX_PATTERN])->id,
        ]),
    ],
    'category field' => [
        fn (): string => route('admin.catalog.category_fields.edit', [
            'id' => CategoryField::factory()->create(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => BREAKOUT_REGEX_PATTERN])->id,
        ]),
    ],
]);

it('keeps a stored regex pattern inside the value attribute of its edit control', function (string $url): void {
    $this->loginAsAdmin();

    $html = get($url)->assertOk()->getContent();

    expect($html)
        ->not->toContain('x"><span>')
        ->not->toContain('<span>{{ constructor')
        ->toContain('value="'.e(BREAKOUT_REGEX_PATTERN).'"');
})->with('regex pattern edit pages');
