<?php

use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\CalculatedAttribute\Services\AttributeRegistry;
use Webkul\Product\Models\Product;

/**
 * Builds a family holding one plain attribute and one calculated attribute
 * derived from it, plus a product carrying a value for the plain one.
 *
 * @return array{product: Product, calculatedCode: string, plainCode: string, expected: string}
 */
function calcFormFixture(?string $formula = null): array
{
    $plainCode = 'mfr_'.Str::random(8);
    $calculatedCode = 'artkey_'.Str::random(8);

    Attribute::factory()->create([
        'code'              => $plainCode,
        'type'              => 'text',
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    Attribute::factory()->create([
        'code'              => $calculatedCode,
        'type'              => 'calculated',
        'formula'           => $formula ?? "concat('-', {$plainCode}, sku)",
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily(
        $family,
        Attribute::whereIn('code', [$plainCode, $calculatedCode])->get()
    );

    $sku = 'CALC-'.Str::random(8);

    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    // `values` is not mass assignable on Product, so set it explicitly.
    $product->values = ['common' => [$plainCode => 'ACME', 'sku' => $sku]];
    $product->save();

    app(AttributeRegistry::class)->flush();

    return [
        'product'        => $product->fresh(),
        'calculatedCode' => $calculatedCode,
        'plainCode'      => $plainCode,
        'expected'       => "ACME-{$sku}",
    ];
}

/**
 * The rendered input carrying the computed value.
 */
function calcReadOnlyInput(string $html, string $expectedValue): string
{
    $pattern = '/<input[^>]*value="'.preg_quote($expectedValue, '/').'"[^>]*>/';

    $matched = preg_match($pattern, $html, $matches);

    expect($matched)->toBe(1, 'expected a rendered input carrying the computed value');

    return $matches[0];
}

it('renders the computed value on the product edit page', function (): void {
    $this->loginAsAdmin();

    $fixture = calcFormFixture();

    $content = $this->get(route('admin.catalog.products.edit', $fixture['product']->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain($fixture['expected']);
});

it('renders the calculated field as read-only', function (): void {
    $this->loginAsAdmin();

    $fixture = calcFormFixture();

    $content = $this->get(route('admin.catalog.products.edit', $fixture['product']->id))
        ->assertOk()
        ->getContent();

    expect(calcReadOnlyInput($content, $fixture['expected']))->toContain('readonly');
});

it('gives the calculated field no name, so nothing is submitted for it', function (): void {
    $this->loginAsAdmin();

    $fixture = calcFormFixture();

    $content = $this->get(route('admin.catalog.products.edit', $fixture['product']->id))
        ->assertOk()
        ->getContent();

    /*
     * The absence of a name attribute is the actual guarantee that a derived
     * value can never be posted back and persisted — the readonly flag alone
     * is a UI affordance and is trivially removed client-side.
     */
    expect(calcReadOnlyInput($content, $fixture['expected']))->not->toContain('name=');

    /*
     * Asserted against form controls specifically, not the whole page: the core
     * template also prints the field name into the label's `for` attribute and
     * into the validation-error binding, and neither of those submits a value.
     */
    $submittable = preg_match(
        '/<(?:input|textarea|select)[^>]*name="'.preg_quote('values[common]['.$fixture['calculatedCode'].']', '/').'"/',
        $content
    );

    expect($submittable)->toBe(0, 'a calculated attribute must have no submittable form control');
});

it('still renders an editable input for the plain attribute beside it', function (): void {
    $this->loginAsAdmin();

    $fixture = calcFormFixture();

    $content = $this->get(route('admin.catalog.products.edit', $fixture['product']->id))
        ->assertOk()
        ->getContent();

    expect($content)->toContain('values[common]['.$fixture['plainCode'].']');
});

it('renders the page rather than failing when a formula no longer compiles', function (): void {
    $this->loginAsAdmin();

    $fixture = calcFormFixture("concat('-', attribute_that_does_not_exist)");

    $this->get(route('admin.catalog.products.edit', $fixture['product']->id))
        ->assertOk();
});
