<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;

/**
 * The formula editor is grafted onto the attribute form through a view-render
 * event, so nothing in core fails loudly if it stops rendering. These assert
 * that it is there, that its help content comes from the function provider
 * rather than a literal, and that its control carries an accessible name —
 * which is the accessibility finding the raw textarea produced.
 */
function calcAttributeWithFormula(): Attribute
{
    return Attribute::factory()->create([
        'code'    => 'calc_'.Str::random(6),
        'type'    => 'calculated',
        'formula' => "concat('-', sku, family)",
    ]);
}

it('renders the formula editor on the attribute create page', function (): void {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.catalog.attributes.create'))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('name="formula"')
        ->and($content)->toContain(trans('calculated_attribute::app.formula.title'));
});

it('renders the stored formula on the attribute edit page', function (): void {
    $this->loginAsAdmin();

    $attribute = calcAttributeWithFormula();

    $content = $this->get(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('name="formula"')
        ->and($content)->toContain(e($attribute->formula));
});

it('gives the formula control an accessible name', function (): void {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.catalog.attributes.create'))
        ->assertOk()
        ->getContent();

    $matched = preg_match('/<textarea[^>]*name="formula"[^>]*>/', $content, $matches);

    expect($matched)->toBe(1, 'expected a rendered textarea for the formula');

    expect($matches[0])->toContain('id="formula"')
        ->and($matches[0])->toContain('aria-label="'.trans('calculated_attribute::app.formula.label').'"');
});

it('lists the callable functions from the provider, not from a literal', function (): void {
    $this->loginAsAdmin();

    $content = $this->get(route('admin.catalog.attributes.create'))
        ->assertOk()
        ->getContent();

    foreach (['category_label_path', 'coalesce', 'pad_left', 'number_format'] as $function) {
        expect($content)->toContain('>'.$function.'<');
    }
});
