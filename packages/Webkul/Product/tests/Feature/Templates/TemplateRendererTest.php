<?php

use Webkul\Product\Contracts\TemplateRenderer as TemplateRendererContract;
use Webkul\Product\Models\Product;
use Webkul\Product\Templates\Results\RenderResult;
use Webkul\Product\Templates\TemplateMessage;
use Webkul\Product\Templates\TemplateRenderer;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->renderer = app(TemplateRendererContract::class);
    $this->channel = $this->templateChannel(['EUR', 'USD'], ['en_US', 'de_DE']);
    $this->otherChannel = $this->templateChannel(['EUR'], ['en_US']);

    $this->name = $this->variableAttribute('tpl_name', 'text', ['value_per_locale' => 1]);
    $this->tagline = $this->variableAttribute('tpl_tagline', 'text', ['value_per_channel' => 1]);
    $this->price = $this->variableAttribute('tpl_price', 'price');
    $this->active = $this->variableAttribute('tpl_active', 'boolean');
    $this->released = $this->variableAttribute('tpl_released', 'date');
    $this->description = $this->templateAttribute('tpl_description');
    $this->richDescription = $this->templateAttribute('tpl_rich_description', ['enable_wysiwyg' => 1]);

    $this->values = [
        'common' => [
            'tpl_price'    => ['EUR' => '309', 'USD' => '329'],
            'tpl_active'   => 'false',
            'tpl_released' => '2026-11-13',
        ],
        'locale_specific' => [
            'en_US' => ['tpl_name' => 'Sunglasses'],
            'de_DE' => ['tpl_name' => 'Sonnenbrillen'],
        ],
        'channel_specific' => [
            $this->channel->code      => ['tpl_tagline' => 'Online exclusive'],
            $this->otherChannel->code => ['tpl_tagline' => 'In store only'],
        ],
    ];
});

function warningKeys(RenderResult $result): array
{
    return array_map(fn (TemplateMessage $warning): string => $warning->key, $result->warnings);
}

it('renders the specification example', function () {
    $result = $this->renderer->render('Introducing {{ tpl_name }}. Price: {{ tpl_price }}.', $this->description, $this->values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('Introducing Sunglasses. Price: 309 EUR, 329 USD.')
        ->and($result->source)->toBe('Introducing {{ tpl_name }}. Price: {{ tpl_price }}.')
        ->and($result->errors)->toBe([])
        ->and($result->warnings)->toBe([]);
});

it('resolves variables in the requested locale', function () {
    $result = $this->renderer->render('{{ tpl_name | upper }}', $this->description, $this->values, $this->channel->code, 'de_DE');

    expect($result->output)->toBe('SONNENBRILLEN');
});

it('resolves variables in the requested channel', function () {
    $source = '{{ tpl_tagline }}';

    expect($this->renderer->render($source, $this->description, $this->values, $this->channel->code, 'en_US')->output)->toBe('Online exclusive')
        ->and($this->renderer->render($source, $this->description, $this->values, $this->otherChannel->code, 'en_US')->output)->toBe('In store only');
});

it('renders a single currency and limits all currencies to the channel', function () {
    expect($this->renderer->render('{{ tpl_price.USD }}', $this->description, $this->values, $this->channel->code, 'en_US')->output)->toBe('329 USD')
        ->and($this->renderer->render('{{ tpl_price }}', $this->description, $this->values, $this->otherChannel->code, 'en_US')->output)->toBe('309 EUR');
});

it('renders a currency missing on the channel as empty with a warning', function () {
    $result = $this->renderer->render('[{{ tpl_price.USD }}]', $this->description, $this->values, $this->otherChannel->code, 'en_US');

    expect($result->output)->toBe('[]')
        ->and(warningKeys($result))->toBe(['currency-not-in-channel']);
});

it('renders missing values as empty and warns once per attribute', function () {
    $result = $this->renderer->render('{{ tpl_name }}|{{ tpl_name }}|{{ tpl_price }}', $this->description, ['common' => []], $this->channel->code, 'en_US');

    expect($result->output)->toBe('||')
        ->and(warningKeys($result))->toBe(['missing-value', 'missing-value'])
        ->and($result->warnings[0]->replace)->toBe(['attribute' => 'tpl_name']);
});

it('does not fall back to another locale', function () {
    $values = ['locale_specific' => ['en_US' => ['tpl_name' => 'Sunglasses']]];

    $result = $this->renderer->render('{{ tpl_name }}', $this->description, $values, $this->channel->code, 'de_DE');

    expect($result->output)->toBe('')
        ->and(warningKeys($result))->toBe(['missing-value']);
});

it('treats false booleans as values, not as missing', function () {
    $result = $this->renderer->render('Active: {{ tpl_active }}', $this->description, $this->values, $this->channel->code, 'de_DE');

    expect($result->output)->toBe('Active: Nein')
        ->and($result->warnings)->toBe([]);
});

it('applies chained filters left to right', function () {
    $result = $this->renderer->render("{{ tpl_name | upper | slug }} {{ tpl_released | format_date('d/m/Y') }}", $this->description, $this->values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('sunglasses 13/11/2026');
});

it('warns about unknown variables and invalid filters without failing', function () {
    $result = $this->renderer->render('{{ tpl_unknown }}-{{ tpl_name | uper }}-{{ tpl_name.EUR }}', $this->description, $this->values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('--')
        ->and(warningKeys($result))->toBe(['unknown-attribute', 'unknown-filter', 'currency-not-allowed']);
});

it('inserts variable values without parsing them again', function () {
    $values = ['locale_specific' => ['en_US' => ['tpl_name' => 'Hello {{ tpl_price }}']]];

    $result = $this->renderer->render('{{ tpl_name }}', $this->description, $values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('Hello {{ tpl_price }}');
});

it('escapes values and purifies rich text output', function () {
    $values = ['locale_specific' => ['en_US' => ['tpl_name' => 'Tom & Jerry <script>alert(1)</script>']]];

    $result = $this->renderer->render('<p><strong>{{ tpl_name }}</strong></p><script>x</script>', $this->richDescription, $values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('<p><strong>Tom &amp; Jerry &lt;script&gt;alert(1)&lt;/script&gt;</strong></p>');
});

it('keeps plain text output unescaped', function () {
    $values = ['locale_specific' => ['en_US' => ['tpl_name' => 'Tom & Jerry']]];

    expect($this->renderer->render('{{ tpl_name }}', $this->description, $values, $this->channel->code, 'en_US')->output)->toBe('Tom & Jerry');
});

it('renders templates referenced by another template', function () {
    $short = $this->templateAttribute('tpl_short_description', ['enable_wysiwyg' => 1]);

    $values = array_merge_recursive($this->values, ['common' => [
        'tpl_short_description' => '<p>{{ tpl_name }} &amp; case</p>',
        'tpl_description'       => 'Short: {{ tpl_short_description | upper }}',
    ]]);

    $result = $this->renderer->render($values['common']['tpl_description'], $this->description, $values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('Short: SUNGLASSES & CASE')
        ->and($short->canHoldTemplate())->toBeTrue();
});

it('stops circular references at render time', function () {
    $this->templateAttribute('tpl_loop');

    $values = ['common' => [
        'tpl_description' => 'A {{ tpl_loop }}',
        'tpl_loop'        => 'B {{ tpl_description }}',
    ]];

    $result = $this->renderer->render($values['common']['tpl_description'], $this->description, $values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('A B ')
        ->and(warningKeys($result))->toBe(['circular-reference'])
        ->and($result->warnings[0]->message())->toContain('tpl_description → tpl_loop → tpl_description');
});

it('limits the depth of nested template references', function () {
    $values = ['common' => []];

    foreach (range(1, TemplateRenderer::MAX_DEPTH + 1) as $level) {
        $this->templateAttribute("tpl_level_{$level}");

        $values['common']["tpl_level_{$level}"] = "{$level} {{ tpl_level_".($level + 1).' }}';
    }

    $values['common']['tpl_level_'.(TemplateRenderer::MAX_DEPTH + 2)] = 'end';

    $result = $this->renderer->render('{{ tpl_level_1 }}', $this->description, $values, $this->channel->code, 'en_US');

    expect(warningKeys($result))->toContain('max-depth')
        ->and($result->output)->toBe('1 2 3 4 ');
});

it('stops rendering once the output exceeds the size budget', function () {
    $this->variableAttribute('tpl_blob', 'text');
    $this->templateAttribute('tpl_chunk');

    $blob = str_repeat('x', 2000);

    $values = ['common' => [
        'tpl_blob'  => $blob,
        'tpl_chunk' => str_repeat('{{ tpl_blob }}', 100),
    ]];

    $result = $this->renderer->render(str_repeat('{{ tpl_chunk }}', 100), $this->description, $values, $this->channel->code, 'en_US');

    expect(warningKeys($result))->toContain('output-too-large')
        ->and(strlen($result->output))->toBeLessThanOrEqual(TemplateRenderer::MAX_RENDERED_BYTES)
        ->and($result->output)->toStartWith($blob);
});

it('counts escaped rich text values against the size budget', function () {
    $values = ['locale_specific' => ['en_US' => ['tpl_name' => str_repeat('"', 200_000)]]];

    $result = $this->renderer->render('<p>{{ tpl_name }}</p>', $this->richDescription, $values, $this->channel->code, 'en_US');

    expect(warningKeys($result))->toBe(['output-too-large'])
        ->and($result->output)->not->toContain('"');
});

it('returns the source unchanged when the attribute does not hold templates', function () {
    $plain = $this->variableAttribute('tpl_plain', 'textarea');

    $result = $this->renderer->render('{{ tpl_name }}', $plain, $this->values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('{{ tpl_name }}')
        ->and($result->warnings)->toBe([]);
});

it('keeps the source of an invalid template and reports the errors', function () {
    $result = $this->renderer->render('Hi {{ tpl_name + 1 }}', $this->description, $this->values, $this->channel->code, 'en_US');

    expect($result->output)->toBe('Hi {{ tpl_name + 1 }}')
        ->and($result->hasErrors())->toBeTrue()
        ->and($result->errors[0]->key)->toBe('invalid-syntax');
});

it('renders a variant with values inherited from its parent without touching the database', function () {
    $parent = Product::factory()->configurable()->create([
        'values' => [
            'common'          => ['tpl_description' => '{{ tpl_name }} for {{ tpl_price.EUR }}'],
            'locale_specific' => ['en_US' => ['tpl_name' => 'Sunglasses']],
        ],
    ]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['tpl_price' => ['EUR' => '199']]],
    ]);

    $storedBefore = $variant->fresh()->values;

    $result = $this->renderer->renderProduct($variant->fresh(), $this->description, $this->channel->code, 'en_US');

    expect($result->source)->toBe('{{ tpl_name }} for {{ tpl_price.EUR }}')
        ->and($result->output)->toBe('Sunglasses for 199 EUR')
        ->and($variant->fresh()->values)->toBe($storedBefore)
        ->and($parent->fresh()->values['common']['tpl_description'])->toBe('{{ tpl_name }} for {{ tpl_price.EUR }}');
});
