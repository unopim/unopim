<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;

/*
 * Regression cover for the stored-active-content finding against the product
 * file attribute ("Spec sheet"). PDFs were stored byte-for-byte and previewed
 * from the public disk inside an unsandboxed iframe on the admin's own origin.
 *
 * SVG is not part of this: FileStorer::storeAs() already runs
 * enshrined/svg-sanitize on every upload, so <script> never reaches disk.
 */
function maliciousPdfBytes(): string
{
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /OpenAction << /S /JavaScript /JS (app.alert\\(1\\);) >> >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
}

function fileAttributeProduct(): array
{
    $attribute = Attribute::factory()->create(['type' => 'file']);

    $product = seedRequiredProductValues(Product::factory()->simple()->create());

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    return [$product, $attribute->code];
}

it('rejects a PDF carrying embedded JavaScript', function () {
    $this->loginAsAdmin();

    [$product, $attributeCode] = fileAttributeProduct();

    Storage::fake();

    $payload = UploadedFile::fake()->createWithContent('payload.pdf', maliciousPdfBytes());

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attributeCode => [$payload]]],
    ]);

    $product->refresh();

    expect($product->values['common'][$attributeCode] ?? null)->toBeNull();
});

it('still accepts a clean PDF', function () {
    $this->loginAsAdmin();

    [$product, $attributeCode] = fileAttributeProduct();

    Storage::fake();

    $clean = UploadedFile::fake()->createWithContent(
        'spec.pdf',
        "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
    );

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attributeCode => [$clean]]],
    ])->assertSessionHas('success');

    $product->refresh();

    expect(Storage::exists($product->values['common'][$attributeCode]))->toBeTrue();
});

it('strips script from an uploaded SVG', function () {
    $this->loginAsAdmin();

    [$product, $attributeCode] = fileAttributeProduct();

    Storage::fake();

    $svg = UploadedFile::fake()->createWithContent(
        'payload.svg',
        '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>',
    );

    $this->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => ['common' => [$attributeCode => [$svg]]],
    ]);

    $product->refresh();

    expect(Storage::get($product->values['common'][$attributeCode]))->not->toContain('<script>');
});

it('serves a previewed PDF with sandboxing headers', function () {
    $this->loginAsAdmin();

    Storage::fake();
    Storage::put('product/1/spec/sheet.pdf', '%PDF-1.4');

    $response = get(route('admin.media.preview', ['path' => 'product/1/spec/sheet.pdf']))->assertOk();

    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->headers->get('Content-Disposition'))->toContain('inline')
        ->and($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("default-src 'none'");
});

it('forces a download for a type that is not inline safe', function () {
    $this->loginAsAdmin();

    Storage::fake();
    Storage::put('product/1/spec/sheet.csv', 'a,b,c');

    $response = get(route('admin.media.preview', ['path' => 'product/1/spec/sheet.csv']))->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('applies the same access control to preview as to download', function () {
    Storage::fake();

    get(route('admin.media.preview', ['path' => 'product/1/spec/sheet.pdf']))
        ->assertRedirect(route('admin.session.create'));

    $this->loginAsAdmin();

    get(route('admin.media.preview', ['path' => 'product/../../.env']))->assertNotFound();

    Storage::put('product/1/f/x.php', '<?php echo "no"; ?>');

    get(route('admin.media.preview', ['path' => 'product/1/f/x.php']))->assertForbidden();
});

it('previews saved files through the access-controlled route', function () {
    $blade = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/media/files.blade.php')
    );

    preg_match('/<iframe[^>]*>/', $blade, $matches);

    expect($matches[0] ?? '')->toContain(':sandbox="previewSandbox"')
        ->and($blade)->toContain("route('admin.media.preview')");
});

/*
 * Chrome refuses to load its PDF viewer inside a sandboxed frame under every
 * token combination (verified in-browser), so PDFs are served unsandboxed and
 * rely on the upload-time active-content check plus the route's CSP. Every
 * other inline type keeps the opaque origin.
 */
it('sandboxes every previewed type except PDF', function () {
    $blade = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/media/files.blade.php')
    );

    expect($blade)->toContain("return this.cardMedia.extension === 'pdf' ? null : '';");
});
