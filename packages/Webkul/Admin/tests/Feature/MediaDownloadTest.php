<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\Attribute;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\get;

it('redirects a guest to login', function () {
    get(route('admin.media.download', ['path' => 'product/1/image/photo.jpg']))
        ->assertRedirect(route('admin.session.create'));
});

it('downloads an allow-listed product media file for an authenticated admin', function () {
    $this->loginAsAdmin();

    Storage::fake();
    Storage::put('product/1/image/photo.jpg', 'fake-image-bytes');

    $response = get(route('admin.media.download', ['path' => 'product/1/image/photo.jpg']))
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('rejects a path traversal attempt with a 404', function () {
    $this->loginAsAdmin();

    Storage::fake();

    get(route('admin.media.download', ['path' => 'product/../../.env']))
        ->assertNotFound();
});

it('rejects a path outside the allow-listed roots with a 404', function () {
    $this->loginAsAdmin();

    Storage::fake();
    Storage::put('framework/cache/x.php', 'not-media');

    get(route('admin.media.download', ['path' => 'framework/cache/x.php']))
        ->assertNotFound();
});

it('rejects an allow-listed root with a disallowed extension with a 403', function () {
    $this->loginAsAdmin();

    Storage::fake();
    Storage::put('product/1/f/x.php', '<?php echo "no"; ?>');

    get(route('admin.media.download', ['path' => 'product/1/f/x.php']))
        ->assertForbidden();
});

it('returns a 404 for an allow-listed, allowed-extension path that does not exist', function () {
    $this->loginAsAdmin();

    Storage::fake();

    get(route('admin.media.download', ['path' => 'product/1/image/missing.jpg']))
        ->assertNotFound();
});

it('rejects a media download for an admin without the owning module permission', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['catalog.categories'],
    ]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    Storage::fake();
    Storage::put('product/1/image/photo.jpg', 'fake-image-bytes');

    get(route('admin.media.download', ['path' => 'product/1/image/photo.jpg']))
        ->assertForbidden()
        ->assertSee(trans('admin::app.common.unauthorized'));
});

it('allows a media download for an admin holding the owning module permission', function () {
    $role = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => ['catalog.categories'],
    ]);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $role->id]));

    Storage::fake();
    Storage::put('category/1/banner/photo.jpg', 'fake-image-bytes');

    get(route('admin.media.download', ['path' => 'category/1/banner/photo.jpg']))
        ->assertOk();
});

it('downloads a stored file whose extension is configured on its owning attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => ['zip', 'pdf']]);

    Storage::fake();
    Storage::put('product/1/'.$attribute->code.'/bundle.zip', 'zip-bytes');

    $response = get(route('admin.media.download', ['path' => 'product/1/'.$attribute->code.'/bundle.zip']))
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('previews a custom-extension file as a download rather than inline', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => ['zip']]);

    Storage::fake();
    Storage::put('product/1/'.$attribute->code.'/bundle.zip', 'zip-bytes');

    $response = get(route('admin.media.preview', ['path' => 'product/1/'.$attribute->code.'/bundle.zip']))
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('rejects a custom extension stored under an attribute that does not allow it', function () {
    $this->loginAsAdmin();

    Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => ['zip']]);
    $other = Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => null]);

    Storage::fake();
    Storage::put('product/1/'.$other->code.'/bundle.zip', 'zip-bytes');

    get(route('admin.media.download', ['path' => 'product/1/'.$other->code.'/bundle.zip']))
        ->assertForbidden();
});

it('rejects a dangerous extension even when an attribute configures it', function (string $extension) {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'file', 'allowed_extensions' => ['zip', $extension]]);

    Storage::fake();
    Storage::put('product/1/'.$attribute->code.'/payload.'.$extension, '<?php echo 1; ?>');

    get(route('admin.media.download', ['path' => 'product/1/'.$attribute->code.'/payload.'.$extension]))
        ->assertForbidden();
})->with(['php', 'phtml', 'phar', 'html', 'htm', 'js', 'svgz']);
