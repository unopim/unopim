<?php

use Webkul\Core\Models\CoreConfig;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;

function enablePassport(): void
{
    CoreConfig::query()->updateOrCreate(
        ['code' => 'catalog.product_passport.settings.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );
}

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
    enablePassport();
});

it('reinstates a withdrawn publication', function () {
    $publication = Publication::factory()->create(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Published);
});

it('rejects reinstating a non-withdrawn publication', function () {
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertStatus(422);
});

it('returns 404 reinstating an unknown publication', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.reinstate', 999999))
        ->assertNotFound();
});

it('redacts a publication with a current version', function () {
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);
    PublicationVersion::factory()->create(['publication_id' => $publication->id, 'is_current' => true]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'GDPR request'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Redacted);
});

it('requires a reason to redact', function () {
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.redact', $publication->id), [])
        ->assertStatus(422);
});

it('rejects redacting a publication with no current versions', function () {
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'GDPR request'])
        ->assertStatus(422);
});

it('writes the passport mapping', function () {
    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.passports.mapping.update'), ['mapping' => []])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('forbids reinstate without publish permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertForbidden();
});

it('rejects unauthenticated redact', function () {
    $publication = Publication::factory()->create();

    $this->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'x'], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
