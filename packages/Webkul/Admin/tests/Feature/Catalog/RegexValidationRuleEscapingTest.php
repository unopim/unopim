<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;
use Webkul\Product\Models\AssociationTypeField;

dataset('hostile regex patterns', [
    'string breakout' => ['^a" + alert(1) + "$'],
    'single quotes'   => ["^a' + alert(1) + '$"],
    'closing script'  => ['^</script><script>alert(1)</script>$'],
    'backslashes'     => ['^\d{3}-\w+\\\\$'],
]);

/**
 * Extract every client-side rules expression bound with `:rules` in the rendered markup.
 *
 * @return list<string>
 */
function renderedRuleExpressions(string $html): array
{
    preg_match_all('/\s:rules="([^"]*)"/', $html, $matches);

    return array_map(fn (string $value): string => html_entity_decode($value, ENT_QUOTES | ENT_HTML5), $matches[1]);
}

it('emits the attribute regex pattern as an inert JSON string literal', function (string $pattern) {
    $attribute = Attribute::factory()->make(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => $pattern, 'is_required' => true]);

    $rules = $attribute->getValidationsField();

    expect(json_decode($rules, true, flags: JSON_THROW_ON_ERROR))->toBe(['required' => true, 'regex' => $pattern])
        ->and($rules)->not->toContain('"'.$pattern.'"')
        ->and($rules)->not->toContain('<')
        ->and($rules)->not->toContain("'");
})->with('hostile regex patterns');

it('emits the category field regex pattern as an inert JSON string literal', function (string $pattern) {
    $field = CategoryField::factory()->make(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => $pattern, 'is_required' => false]);

    $rules = $field->getValidationsField();

    expect(json_decode($rules, true, flags: JSON_THROW_ON_ERROR))->toBe(['regex' => $pattern])
        ->and($rules)->not->toContain('<')
        ->and($rules)->not->toContain("'");
})->with('hostile regex patterns');

it('emits the association field regex pattern as an inert JSON string literal', function (string $pattern) {
    $field = new AssociationTypeField(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => $pattern, 'is_required' => true]);

    $rules = $field->getValidationsField();

    expect(json_decode($rules, true, flags: JSON_THROW_ON_ERROR))->toBe(['required' => true, 'regex' => $pattern])
        ->and($rules)->not->toContain('<')
        ->and($rules)->not->toContain("'");
})->with('hostile regex patterns');

it('keeps the media and validation rules as a JSON object', function () {
    $image = Attribute::factory()->make(['type' => 'image', 'max_file_size' => 3, 'is_required' => true]);
    $price = Attribute::factory()->make(['type' => 'price', 'validation' => 'number']);
    $plain = Attribute::factory()->make(['type' => 'text', 'validation' => 'email']);
    $none = Attribute::factory()->make(['type' => 'text', 'validation' => null, 'is_required' => false]);

    expect(json_decode($image->getValidationsField(), true))->toBe([
        'required' => true,
        'size'     => 3072,
        'mimes'    => ['image/bmp', 'image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
    ])
        ->and(json_decode($price->getValidationsField(), true))->toBe(['decimal' => true, 'numeric' => true])
        ->and(json_decode($plain->getValidationsField(), true))->toBe(['email' => true])
        ->and($none->getValidationsField())->toBe('{}');
});

it('renders a hostile attribute regex pattern as an inert rules literal on the product form', function (string $pattern) {
    $attribute = Attribute::factory()->create(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => $pattern, 'value_per_locale' => false, 'value_per_channel' => false]);

    $html = (string) $this->blade(
        '<x-admin::products.dynamic-attribute-fields :fields="$fields" />',
        ['fields' => collect([$attribute])],
    );

    $expressions = renderedRuleExpressions($html);

    expect($expressions)->not->toBeEmpty();

    foreach ($expressions as $expression) {
        expect(json_decode($expression, true, flags: JSON_THROW_ON_ERROR))->toBe(['regex' => $pattern]);
    }
})->with('hostile regex patterns');

it('renders a hostile category field regex pattern as an inert rules literal on the category form', function (string $pattern) {
    $field = CategoryField::factory()->create(['type' => 'text', 'validation' => 'regex', 'regex_pattern' => $pattern, 'is_required' => false, 'value_per_locale' => false]);

    $html = (string) $this->blade(
        '<x-admin::categories.dynamic-fields :fields="$fields" />',
        ['fields' => collect([$field])],
    );

    $expressions = renderedRuleExpressions($html);

    expect($expressions)->not->toBeEmpty();

    foreach ($expressions as $expression) {
        expect(json_decode($expression, true, flags: JSON_THROW_ON_ERROR))->toBe(['regex' => $pattern]);
    }
})->with('hostile regex patterns');
