<?php

use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Services\Publisher;

it('serves a document referenced by the current published version', function (): void {
    [$version, $path] = $this->passportWithDocumentFixture();

    $this->get('/p/'.$version->publication->uuid.'/asset/'.$path)
        ->assertOk()
        ->assertHeader('Content-Disposition', 'attachment; filename="certificate.pdf"')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});

it('404s a path not referenced by any current version', function (): void {
    [$version] = $this->passportWithDocumentFixture();

    $this->get('/p/'.$version->publication->uuid.'/asset/publication/unrelated/secret-costing.pdf')
        ->assertNotFound();
});

it('404s path traversal attempts instead of 500ing', function (string $attempt): void {
    [$version] = $this->passportWithDocumentFixture();

    $this->get('/p/'.$version->publication->uuid.'/asset/'.$attempt)->assertNotFound();
})->with([
    '../../../.env',
    '..%2F..%2F.env',
    '/etc/passwd',
    // A raw embedded newline is rejected by Symfony's Request::create() before
    // the app ever sees it (and no real HTTP client can send one over the
    // wire); %0A is how a control character would actually reach us, and
    // exercises the same control-character guard in sanitizePath().
    'line1%0Aline2.pdf',
]);

it('stops serving a document the moment its publication is withdrawn', function (): void {
    [$version, $path] = $this->passportWithDocumentFixture();

    $version->publication->update(['status' => PublicationStatus::Withdrawn]);

    $this->get('/p/'.$version->publication->uuid.'/asset/'.$path)->assertNotFound();
});

it('revokes a document immediately on redaction', function (): void {
    [$version, $path] = $this->passportWithDocumentFixture();

    resolve(Publisher::class)->redactAll($version->publication, $this->loginAsAdmin()->id, 'gdpr request');

    $this->get('/p/'.$version->publication->uuid.'/asset/'.$path)->assertNotFound();
});
