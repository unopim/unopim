<?php

use Illuminate\Support\Facades\DB;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\PublicationVersionDocumentProxy;
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
    // %0A, not a raw newline (which Request::create() rejects first), exercises sanitizePath()'s control-char guard.
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

it('404s a document once the per-channel kill switch is disabled, even though the file exists and is referenced by a current published version', function (): void {
    [$version, $path] = $this->passportWithDocumentFixture();

    DB::table('core_config')->updateOrInsert(
        ['code' => 'general.publication.settings.enabled', 'channel_code' => $version->publication->channel->code],
        ['value' => '0']
    );

    app('config')->set('core_config', null);

    $this->get('/p/'.$version->publication->uuid.'/asset/'.$path)->assertNotFound();
});

it('keeps the document index rows of a superseded version', function (): void {
    [$first, $firstPath] = $this->passportWithDocumentFixture();

    [$second, $secondPath] = $this->republishWithDocument($first, 'certificate-reissued.pdf');

    $rows = PublicationVersionDocumentProxy::modelClass()::query()
        ->where('publication_id', $first->publication_id)
        ->pluck('path', 'publication_version_id');

    expect($rows->get($first->id))->toBe($firstPath)
        ->and($rows->get($second->id))->toBe($secondPath);
});

it('serves only the paths the current version references once a version is superseded', function (): void {
    [$first, $firstPath] = $this->passportWithDocumentFixture();

    [, $secondPath] = $this->republishWithDocument($first, 'certificate-reissued.pdf');

    $uuid = $first->publication->uuid;

    $this->get('/p/'.$uuid.'/asset/'.$secondPath)->assertOk();
    $this->get('/p/'.$uuid.'/asset/'.$firstPath)->assertNotFound();
});
