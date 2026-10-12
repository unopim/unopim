<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Webkul\Admin\Http\Controllers\TinyMCEController;
use Webkul\Admin\Http\Requests\TinyMCEUploadRequest;

/**
 * Regression coverage for the TinyMCE unrestricted-upload fix: the endpoint now
 * validates an image allowlist (real extension + MIME match) and stores the file
 * under a randomised name, so HTML/SVG (stored XSS) or .php (RCE) files can no
 * longer be written to the public path.
 *
 * Driven against the request rules + controller sink so it holds regardless of
 * APP_URL / HTTP setup.
 */
function tinymceRules(): array
{
    return (new TinyMCEUploadRequest)->rules();
}

describe('TinyMCE upload — file-type validation', function () {
    it('rejects a .php upload', function () {
        $php = UploadedFile::fake()->createWithContent('shell.php', "<?php echo 'pwned'; ?>");

        expect(Validator::make(['file' => $php], tinymceRules())->fails())->toBeTrue();
    });

    it('rejects an .html upload', function () {
        $html = UploadedFile::fake()->createWithContent('xss.html', '<script>alert(1)</script>');

        expect(Validator::make(['file' => $html], tinymceRules())->fails())->toBeTrue();
    });

    it('rejects an .svg upload', function () {
        $svg = UploadedFile::fake()->createWithContent('x.svg', '<svg onload="alert(1)"></svg>');

        expect(Validator::make(['file' => $svg], tinymceRules())->fails())->toBeTrue();
    });

    it('accepts a genuine png', function () {
        $png = UploadedFile::fake()->image('photo.png', 10, 10);

        expect(Validator::make(['file' => $png], tinymceRules())->fails())->toBeFalse();
    });
});

describe('TinyMCE upload — safe storage', function () {
    it('stores a valid image under a randomised name, not the client name', function () {
        Storage::fake('public');

        $png = UploadedFile::fake()->image('photo.png', 10, 10);

        $request = TinyMCEUploadRequest::create('/admin/tinymce/upload', 'POST');
        $request->files->set('file', $png);

        $result = app(TinyMCEController::class)->storeMedia($request);

        expect($result['file_name'])
            ->not->toBe('photo.png')
            ->toEndWith('.png');
        expect($result['file'])->not->toContain('photo.png');
        Storage::disk('public')->assertExists($result['file']);
    });
});

describe('TinyMCE upload — authorization', function () {
    it('rejects authenticated admins without a content-edit permission', function () {
        Storage::fake('public');
        $this->loginWithPermissions();

        expect(bouncer()->hasPermission('catalog.products.edit'))->toBeFalse()
            ->and(bouncer()->hasPermission('catalog.categories.edit'))->toBeFalse()
            ->and(bouncer()->hasPermission('ai-agent.prompt.edit'))->toBeFalse();

        $this->post(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->image('photo.png'),
        ])->assertForbidden();

        expect(Storage::disk('public')->allFiles('tinymce'))->toBeEmpty();
    });

    it('allows product editors to upload a validated image', function () {
        Storage::fake('public');
        $this->loginWithPermissions('custom', ['catalog.products.edit']);

        $this->post(route('admin.tinymce.upload'), [
            'file' => UploadedFile::fake()->image('photo.png'),
        ])->assertOk();

        expect(Storage::disk('public')->allFiles('tinymce'))->toHaveCount(1);
    });
});
