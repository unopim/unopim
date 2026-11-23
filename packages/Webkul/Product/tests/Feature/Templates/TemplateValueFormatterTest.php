<?php

use Webkul\Attribute\Contracts\Attribute as AttributeContract;
use Webkul\Attribute\Services\AttributeNormalizerFactory;
use Webkul\Attribute\Services\Normalizers\AbstractNormalizer;
use Webkul\Product\Templates\TemplateValueFormatter;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    $this->formatter = app(TemplateValueFormatter::class);
    $this->channel = $this->templateChannel(['EUR', 'USD']);
});

it('treats only null, empty strings and empty arrays as missing', function (mixed $value, ?string $expected) {
    $attribute = $this->variableAttribute('tpl_text');

    expect($this->formatter->format($attribute, $value, $this->channel->code, 'en_US'))->toBe($expected);
})->with([
    'null'         => [null, null],
    'empty string' => ['', null],
    'empty array'  => [[], null],
    'zero string'  => ['0', '0'],
    'text'         => ['Sunglasses', 'Sunglasses'],
]);

it('converts rich text variables to plain text', function () {
    $attribute = $this->variableAttribute('tpl_rich', 'textarea', ['enable_wysiwyg' => 1]);

    expect($this->formatter->format($attribute, '<p>Polarized &amp; light</p><p>UV400</p>', $this->channel->code, 'en_US'))
        ->toBe('Polarized & light UV400');
});

it('formats every channel currency of a price in channel order', function () {
    $price = $this->variableAttribute('tpl_price', 'price');

    $value = ['USD' => '329', 'GBP' => '280', 'EUR' => '309'];

    expect($this->formatter->format($price, $value, $this->channel->code, 'en_US'))->toBe('309 EUR, 329 USD')
        ->and($this->formatter->format($price, $value, $this->channel->code, 'en_US', 'GBP'))->toBe('280 GBP')
        ->and($this->formatter->format($price, ['USD' => '329'], $this->channel->code, 'en_US', 'EUR'))->toBeNull()
        ->and($this->formatter->format($price, $value, 'unknown_channel', 'en_US'))->toBe('329 USD, 280 GBP, 309 EUR');
});

it('translates boolean values into the requested locale', function () {
    $boolean = $this->variableAttribute('tpl_boolean', 'boolean');

    expect($this->formatter->format($boolean, 'true', $this->channel->code, 'en_US'))->toBe('Yes')
        ->and($this->formatter->format($boolean, 'false', $this->channel->code, 'en_US'))->toBe('No')
        ->and($this->formatter->format($boolean, false, $this->channel->code, 'en_US'))->toBe('No')
        ->and($this->formatter->format($boolean, 'true', $this->channel->code, 'de_DE'))->toBe('Ja');
});

it('uses translated option labels and falls back to the option code', function () {
    $colors = $this->variableAttribute('tpl_colors', 'multiselect');

    $this->attributeOption($colors, 'black', ['en_US' => 'Black', 'de_DE' => 'Schwarz']);
    $this->attributeOption($colors, 'blue', ['en_US' => 'Blue']);

    expect($this->formatter->format($colors, 'black,blue', $this->channel->code, 'en_US'))->toBe('Black, Blue')
        ->and($this->formatter->format($colors, ['black', 'blue'], $this->channel->code, 'de_DE'))->toBe('Schwarz, blue')
        ->and($this->formatter->format($colors, 'missing_option', $this->channel->code, 'en_US'))->toBe('missing_option');
});

it('formats measurements through the registered measurement normalizer', function () {
    $normalizer = new class extends AbstractNormalizer
    {
        public function getData(mixed $data, ?AttributeContract $attribute = null, array $options = [])
        {
            return $options['format'] === 'datagrid' ? $data['amount'].' cm' : $data;
        }
    };

    $this->mock(AttributeNormalizerFactory::class)
        ->shouldReceive('getNormalizer')
        ->with('measurement')
        ->andReturn($normalizer);

    $length = $this->variableAttribute('tpl_length', 'measurement');

    expect(app(TemplateValueFormatter::class)->format($length, ['amount' => '10', 'unit' => 'CENTIMETER'], $this->channel->code, 'en_US'))
        ->toBe('10 cm');
});

it('supports only the allowed variable types', function (string $type, bool $supported) {
    expect($this->formatter->supports($this->variableAttribute('tpl_'.$type, $type)))->toBe($supported);
})->with([
    ['text', true],
    ['textarea', true],
    ['price', true],
    ['boolean', true],
    ['select', true],
    ['multiselect', true],
    ['checkbox', true],
    ['date', true],
    ['datetime', true],
    ['image', false],
    ['file', false],
    ['gallery', false],
]);
