<?php

use Illuminate\Http\UploadedFile;

use function Pest\Laravel\postJson;

it('redirects a guest to login', function () {
    $response = postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.7\n%%EOF"),
    ]);

    $response->assertRedirect(route('admin.session.create'));
});

it('accepts a clean PDF at upload time', function () {
    $this->loginAsAdmin();

    $payload = "%PDF-1.7\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";

    postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('spec-sheet.pdf', $payload),
    ])->assertOk()
        ->assertJson(['valid' => true]);
});

it('rejects a PDF carrying an embedded JavaScript action at upload time', function () {
    $this->loginAsAdmin();

    $payload = "%PDF-1.7\n1 0 obj<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n%%EOF";

    postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('payload.pdf', $payload),
    ])->assertStatus(422)
        ->assertJson(['valid' => false]);
});

it('rejects an SVG carrying an embedded script tag at upload time', function () {
    $this->loginAsAdmin();

    $payload = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>';

    postJson(route('admin.media.scan'), [
        'file'                  => UploadedFile::fake()->createWithContent('payload.svg', $payload),
        'is_image'              => 0,
        'accepted_extensions'   => ['svg'],
    ])->assertStatus(422);
});

it('accepts a clean image at upload time', function () {
    $this->loginAsAdmin();

    postJson(route('admin.media.scan'), [
        'file'     => UploadedFile::fake()->image('photo.png'),
        'is_image' => 1,
    ])->assertOk()
        ->assertJson(['valid' => true]);
});

it('accepts a clean image at upload time when is_image is false but accepted_extensions lists image types (gallery widget payload)', function () {
    $this->loginAsAdmin();

    postJson(route('admin.media.scan'), [
        'file'                => UploadedFile::fake()->image('photo.png'),
        'is_image'            => 0,
        'accepted_extensions' => ['png', 'jpg', 'jpeg', 'gif', 'webp'],
    ])->assertOk()
        ->assertJson(['valid' => true]);
});
