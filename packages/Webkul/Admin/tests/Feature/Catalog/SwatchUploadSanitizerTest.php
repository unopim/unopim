<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Repositories\AttributeOptionRepository;

/**
 * Regression coverage for the swatch-image upload sanitizer bypass (audit finding #6).
 * Swatch uploads now go through FileStorer (SVG sanitized) and are validated against a
 * real-extension image allowlist (HTML / non-image files rejected).
 */
beforeEach(fn () => $this->loginAsAdmin());

it('rejects a non-image (HTML) file uploaded as a swatch image', function () {
    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $html = UploadedFile::fake()->createWithContent('evil.html', '<script>alert(document.domain)</script>');

    $this->post(route('admin.catalog.attributes.options.store', $attribute->id), [
        'code'         => 'swatch_html_'.uniqid(),
        'locales'      => ['en_US' => ['label' => 'x']],
        'swatch_value' => $html,
    ], ['Accept' => 'application/json'])->assertStatus(422)->assertJsonValidationErrors('swatch_value');
});

it('sanitizes an SVG swatch image so no script/onload survives', function () {
    Storage::fake('public');

    $attribute = Attribute::factory()->create(['type' => 'select', 'swatch_type' => 'image']);

    $tmp = tempnam(sys_get_temp_dir(), 'svg');
    file_put_contents($tmp, '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script></svg>');
    $svg = new UploadedFile($tmp, 'swatch.svg', 'image/svg+xml', null, true);

    $code = 'swatch_svg_'.uniqid();
    app(AttributeOptionRepository::class)->create([
        'attribute_id' => $attribute->id,
        'code'         => $code,
        'swatch_value' => $svg,
    ]);

    $option = AttributeOption::where('attribute_id', $attribute->id)->where('code', $code)->first();

    expect($option->swatch_value)->not->toBeNull()
        ->and($option->swatch_value)->toEndWith('.svg');

    $stored = Storage::disk('public')->get($option->swatch_value);
    expect($stored)->not->toContain('<script')
        ->and($stored)->not->toContain('onload');

    @unlink($tmp);
});
