<?php

use Webkul\Product\Templates\Expression;
use Webkul\Product\Templates\FilterCall;
use Webkul\Product\Templates\Filters\FilterRegistry;
use Webkul\Product\Templates\Filters\FormatDateFilter;
use Webkul\Product\Templates\TemplateValueFormatter;

beforeEach(function () {
    $this->filters = app(FilterRegistry::class);
});

it('exposes exactly the allow-listed filters', function () {
    expect($this->filters->names())->toBe(['capitalize', 'format_date', 'lower', 'slug', 'title', 'upper'])
        ->and($this->filters->has('date'))->toBeFalse()
        ->and($this->filters->has('raw'))->toBeFalse();
});

it('describes every filter for the editor suggestions', function () {
    $suggestions = collect($this->filters->suggestions())->keyBy('name');

    expect($suggestions->keys()->all())->toBe($this->filters->names())
        ->and($suggestions['upper']['value'])->toBe('upper')
        ->and($suggestions['upper']['types'])->toBe(TemplateValueFormatter::SUPPORTED_TYPES)
        ->and($suggestions['format_date']['value'])->toBe("format_date('d.m.Y')")
        ->and($suggestions['format_date']['types'])->toBe(['date', 'datetime']);
});

it('is shared as a single registry instance', function () {
    expect(app(FilterRegistry::class))->toBe($this->filters);
});

it('applies the text filters', function (string $filter, string $input, string $expected) {
    expect($this->filters->get($filter)->apply($input, null, 'en_US'))->toBe($expected);
})->with([
    'upper'                => ['upper', 'Sunglasses', 'SUNGLASSES'],
    'lower'                => ['lower', 'SunGlasses', 'sunglasses'],
    'title'                => ['title', 'polarized sunglasses', 'Polarized Sunglasses'],
    'capitalize'           => ['capitalize', 'sUNGLASSES deluxe', 'Sunglasses deluxe'],
    'slug'                 => ['slug', 'Polarized Sunglasses & Case!', 'polarized-sunglasses-case'],
    'multibyte upper'      => ['upper', 'café crème', 'CAFÉ CRÈME'],
    'multibyte title'      => ['title', 'élan vital', 'Élan Vital'],
    'multibyte capitalize' => ['capitalize', 'ÉCLAIR', 'Éclair'],
]);

it('transliterates slugs with the language of the locale', function () {
    $slug = $this->filters->get('slug');

    expect($slug->apply('Größe Übermaß', null, 'de_DE'))->toBe('groesse-uebermass')
        ->and($slug->apply('Größe', null, 'en_US'))->toBe('grosse');
});

it('formats dates with a php date format in the requested locale', function () {
    $formatDate = $this->filters->get('format_date');

    expect($formatDate->apply('2026-11-13', 'd/m/Y', 'en_US'))->toBe('13/11/2026')
        ->and($formatDate->apply('2026-11-13', 'l, j F Y', 'en_US'))->toBe('Friday, 13 November 2026')
        ->and($formatDate->apply('2026-11-13', 'l, j. F Y', 'de_DE'))->toBe('Freitag, 13. November 2026')
        ->and($formatDate->apply('2026-03-02 14:30:00', 'H:i', 'en_US'))->toBe('14:30')
        ->and($formatDate->apply('2026-03-02', 'F', 'fr_FR'))->toBe('mars');
});

it('fails safely when a date cannot be parsed', function () {
    expect($this->filters->get('format_date')->apply('not a date', 'Y', 'en_US'))->toBeNull();
});

it('restricts the date format argument', function (?string $argument, bool $accepted) {
    expect((new FormatDateFilter)->acceptsArgument($argument))->toBe($accepted);
})->with([
    'missing'          => [null, false],
    'empty'            => ['', false],
    'date'             => ['d/m/Y', true],
    'datetime'         => ['Y-m-d H:i:s', true],
    'too long'         => [str_repeat('d', 31), false],
    'escaped literal'  => ['\\Y', false],
    'markup'           => ['<b>Y</b>', false],
    'unknown letters'  => ['qqq', false],
    'trailing newline' => ["Y-m-d\n", false],
]);

it('rejects an argument on filters that take none', function () {
    expect($this->filters->get('upper')->acceptsArgument(null))->toBeTrue()
        ->and($this->filters->get('upper')->acceptsArgument('x'))->toBeFalse();
});

it('only formats date and datetime attributes', function (string $type, bool $supported) {
    expect($this->filters->get('format_date')->supports($type))->toBe($supported);
})->with([
    ['date', true],
    ['datetime', true],
    ['text', false],
    ['price', false],
]);

it('inspects every filter call of an expression', function (array $filters, string $type, ?string $expectedKey) {
    $problem = $this->filters->inspect(new Expression('released', 'released', null, $filters), $type);

    expect($problem?->key)->toBe($expectedKey);
})->with([
    'valid chain'                => [[new FilterCall('format_date', 'F'), new FilterCall('upper')], 'date', null],
    'unknown filter'             => [[new FilterCall('uper')], 'text', 'unknown-filter'],
    'date filter on text'        => [[new FilterCall('format_date', 'Y')], 'text', 'filter-not-supported'],
    'date filter after another'  => [[new FilterCall('upper'), new FilterCall('format_date', 'Y')], 'date', 'format-date-position'],
    'date filter without format' => [[new FilterCall('format_date')], 'date', 'invalid-filter-argument'],
    'argument on text filter'    => [[new FilterCall('upper', 'x')], 'text', 'invalid-filter-argument'],
]);

it('lists the available filters when one is unknown', function () {
    $problem = $this->filters->inspect(new Expression('name | raw', 'name', null, [new FilterCall('raw')]), 'text');

    expect($problem->message())->toContain('raw')
        ->and($problem->message())->toContain('capitalize, format_date, lower, slug, title, upper');
});
