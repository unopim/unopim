<?php

use Webkul\Product\Templates\Expression;
use Webkul\Product\Templates\FilterCall;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateParser;

beforeEach(function () {
    $this->parser = new TemplateParser;
});

it('splits a template into literal text and expressions', function () {
    $template = $this->parser->parse('Introducing {{ name }}. Price: {{ price }}.');

    expect($template->hasErrors())->toBeFalse()
        ->and($template->segments)->toHaveCount(5)
        ->and($template->segments[0])->toBe('Introducing ')
        ->and($template->segments[1])->toBeInstanceOf(Expression::class)
        ->and($template->segments[4])->toBe('.')
        ->and($template->attributeCodes())->toBe(['name', 'price']);
});

it('keeps a value without expressions as a single literal segment', function () {
    $template = $this->parser->parse('Plain {single} text');

    expect($template->segments)->toBe(['Plain {single} text'])
        ->and($template->expressions())->toBe([])
        ->and($template->hasErrors())->toBeFalse();
});

it('lists each referenced attribute code once', function () {
    expect($this->parser->parse('{{ name }} {{ name | upper }} {{ sku }}')->attributeCodes())->toBe(['name', 'sku']);
});

it('parses filters, filter arguments and a currency suffix', function () {
    $expression = $this->parser->parse("{{ price.EUR | lower | format_date('d/m/Y') | slug }}")->expressions()[0];

    expect($expression->attributeCode)->toBe('price')
        ->and($expression->currency)->toBe('EUR')
        ->and($expression->filters)->toEqual([
            new FilterCall('lower'),
            new FilterCall('format_date', 'd/m/Y'),
            new FilterCall('slug'),
        ]);
});

it('accepts compact spacing and double quoted arguments', function (string $source, array $filters) {
    $template = $this->parser->parse($source);

    expect($template->hasErrors())->toBeFalse()
        ->and($template->expressions()[0]->filters)->toEqual($filters);
})->with([
    'no spaces'            => ['{{name|upper}}', [new FilterCall('upper')]],
    'double quotes'        => ['{{ released | format_date("Y") }}', [new FilterCall('format_date', 'Y')]],
    'spaced argument'      => ["{{ released | format_date( 'd.m.Y' ) }}", [new FilterCall('format_date', 'd.m.Y')]],
    'multiline expression' => ["{{\n  name\n  | title\n}}", [new FilterCall('title')]],
]);

it('rejects malformed expressions as invalid syntax', function (string $source) {
    $template = $this->parser->parse($source);

    expect($template->errors)->toHaveCount(1)
        ->and($template->errors[0]->key)->toBe('invalid-syntax')
        ->and($template->expressions())->toBe([]);
})->with([
    'empty expression'       => '{{ }}',
    'method call'            => '{{ name.upper() }}',
    'function call'          => '{{ system("ls") }}',
    'nested expression'      => '{{ name {{ price }}',
    'empty filter arguments' => '{{ name | upper() }}',
    'unquoted argument'      => '{{ released | format_date(Y) }}',
    'missing filter name'    => '{{ name | }}',
    'arithmetic'             => '{{ price + 1 }}',
    'deep property access'   => '{{ price.EUR.amount }}',
    'html inside expression' => '{{ <b>name</b> }}',
]);

it('keeps an invalid expression as literal text', function () {
    expect($this->parser->parse('Hello {{ name + 1 }}!')->segments)->toBe(['Hello ', '{{ name + 1 }}', '!']);
});

it('reports an expression that is opened but never closed', function () {
    $template = $this->parser->parse('{{ name }} and {{ price');

    expect(array_map(fn (TemplateMessage $error): string => $error->key, $template->errors))->toBe(['unclosed-expression']);
});

it('limits the number of expressions in one template', function () {
    $within = $this->parser->parse(str_repeat('{{ name }}', TemplateParser::MAX_EXPRESSIONS));
    $beyond = $this->parser->parse(str_repeat('{{ name }}', TemplateParser::MAX_EXPRESSIONS + 1));

    expect($within->hasErrors())->toBeFalse()
        ->and($beyond->errors[0]->key)->toBe('too-many-expressions')
        ->and($beyond->expressions())->toBe([]);
});

it('limits the number of filters in one expression', function () {
    $template = $this->parser->parse('{{ name | upper | lower | title | slug | capitalize | upper }}');

    expect($template->errors[0]->key)->toBe('too-many-filters')
        ->and($template->errors[0]->replace['max'])->toBe(TemplateParser::MAX_FILTERS);
});

it('limits the byte length of a template source', function () {
    $within = $this->parser->parse('{{ name }}'.str_repeat('x', TemplateParser::MAX_SOURCE_BYTES - 10));
    $beyond = $this->parser->parse('{{ name }}'.str_repeat('x', TemplateParser::MAX_SOURCE_BYTES - 9));

    expect($within->hasErrors())->toBeFalse()
        ->and($beyond->errors)->toEqual([new TemplateMessage('source-too-long', ['max' => TemplateParser::MAX_SOURCE_BYTES])])
        ->and($beyond->expressions())->toBe([]);
});

it('ignores the byte limit for long text without expressions', function () {
    expect($this->parser->parse(str_repeat('{x} ', TemplateParser::MAX_SOURCE_BYTES))->hasErrors())->toBeFalse();
});

it('reports a flood of opening braces as one unclosed expression', function () {
    $template = $this->parser->parse(str_repeat('{{', 30_000).'}');

    expect($template->errors)->toEqual([new TemplateMessage('unclosed-expression')]);
});

it('reports a template the pattern engine fails on as too complex', function () {
    $limit = ini_get('pcre.backtrack_limit');

    ini_set('pcre.backtrack_limit', '1');

    try {
        $template = $this->parser->parse('Hi {{ name | upper }}');
    } finally {
        ini_set('pcre.backtrack_limit', $limit);
    }

    expect($template->errors)->toEqual([new TemplateMessage('too-complex')])
        ->and($template->expressions())->toBe([]);
});

it('decodes editor entities inside expressions of rich text', function () {
    $source = '<p>{{&nbsp;released | format_date(&#39;d/m/Y&#39;) }} &amp; {{'."\u{00A0}".'name }}</p>';

    $html = $this->parser->parse($source, html: true);

    expect($html->hasErrors())->toBeFalse()
        ->and($html->attributeCodes())->toBe(['released', 'name'])
        ->and($html->expressions()[0]->filters)->toEqual([new FilterCall('format_date', 'd/m/Y')])
        ->and($html->segments[2])->toBe(' &amp; ')
        ->and($this->parser->parse($source)->hasErrors())->toBeTrue();
});

it('never echoes curly braces back in error labels', function () {
    $template = $this->parser->parse('{{ constructor {{ x }}');

    expect($template->errors[0]->replace['expression'])->not->toContain('{')
        ->and($template->errors[0]->replace['expression'])->not->toContain('}');
});

it('reuses the parsed tree for an identical source', function () {
    expect($this->parser->parse('{{ name }}'))->toBe($this->parser->parse('{{ name }}'))
        ->and($this->parser->parse('{{ name }}'))->not->toBe($this->parser->parse('{{ name }}', html: true));
});

it('detects whether a value holds a template', function (mixed $value, bool $expected) {
    expect($this->parser->containsTemplate($value))->toBe($expected);
})->with([
    'template'     => ['Hi {{ name }}', true],
    'single brace' => ['Hi {name}', false],
    'array'        => [['{{ name }}'], false],
    'null'         => [null, false],
]);
