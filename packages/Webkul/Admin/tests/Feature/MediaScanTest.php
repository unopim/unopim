<?php

use Illuminate\Http\UploadedFile;

it('redirects a guest to login', function () {
    $this->postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('doc.pdf', "%PDF-1.7\n%%EOF"),
    ])->assertRedirect(route('admin.session.create'));
});

it('accepts a clean pdf at pick time', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('spec-sheet.pdf', "%PDF-1.7\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"),
    ])->assertOk()->assertJson(['valid' => true]);
});

it('rejects a pdf carrying an embedded javascript action at pick time', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'), [
        'file' => UploadedFile::fake()->createWithContent('payload.pdf', "%PDF-1.7\n1 0 obj<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n%%EOF"),
    ])->assertStatus(422)
        ->assertJson(['valid' => false])
        ->assertJsonPath('message', fn (string $message) => str_contains($message, trans('core::validation.active-content-reasons.embedded_javascript_or_action')));
});

it('rejects a file whose extension the widget does not accept', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'), [
        'file'                => UploadedFile::fake()->createWithContent('spec-sheet.pdf', "%PDF-1.7\n1 0 obj<</Type/Catalog>>endobj\n%%EOF"),
        'accepted_extensions' => ['csv', 'txt'],
    ])->assertStatus(422)->assertJson(['valid' => false]);
});

it('accepts a clean image for an image widget', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'), [
        'file'     => UploadedFile::fake()->image('photo.png'),
        'is_image' => 1,
    ])->assertOk()->assertJson(['valid' => true]);
});

it('rejects malformed extension lists', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'), [
        'file'                => UploadedFile::fake()->image('photo.png'),
        'accepted_extensions' => ['../png'],
    ])->assertStatus(422)->assertJsonValidationErrors('accepted_extensions.0');
});

it('requires a file', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.media.scan'))->assertStatus(422)->assertJsonValidationErrors('file');
});
