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

/**
 * Create a published publication with a current version.
 */
function publishedPublicationWithCurrentVersion(): Publication
{
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);
    PublicationVersion::factory()->create(['publication_id' => $publication->id, 'is_current' => true]);

    return $publication;
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
    $publication = publishedPublicationWithCurrentVersion();

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

it('forbids reinstate with a view-only key', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertForbidden();
});

it('maps redact to its own scope key, distinct from withdraw', function (): void {
    $roles = app('api-acl')->roles;

    expect($roles['admin.api.passports.redact'])->toBe('api.catalog.passports.redact')
        ->and($roles['admin.api.passports.withdraw'])->toBe('api.catalog.passports.withdraw');
});

it('authorises reinstate with the withdraw scope, like the admin panel', function (): void {
    expect(app('api-acl')->roles['admin.api.passports.reinstate'])->toBe('api.catalog.passports.withdraw');
});

it('forbids reinstate with a publish-only key and keeps the passport withdrawn', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.publish']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertForbidden();

    expect($publication->fresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('reinstates with a withdraw-only key', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.withdraw']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Withdrawn]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.reinstate', $publication->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Published);
});

it('forbids redact with a withdraw-only key and keeps the payload', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.withdraw']);
    $publication = publishedPublicationWithCurrentVersion();

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'GDPR request'])
        ->assertForbidden();

    expect($publication->fresh()->status)->toBe(PublicationStatus::Published)
        ->and(PublicationVersion::query()->where('publication_id', $publication->id)->whereNull('redacted_at')->count())->toBe(1);
});

it('still lets a withdraw-only key withdraw', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.withdraw']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.withdraw', $publication->id))
        ->assertOk();

    expect($publication->fresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('redacts with a key holding the dedicated redact scope', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.redact']);
    $publication = publishedPublicationWithCurrentVersion();

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'GDPR request'])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($publication->fresh()->status)->toBe(PublicationStatus::Redacted);
});

it('forbids withdraw with a redact-only key', function (): void {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.passports.redact']);
    $publication = Publication::factory()->create(['status' => PublicationStatus::Published]);

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.passports.withdraw', $publication->id))
        ->assertForbidden();

    expect($publication->fresh()->status)->toBe(PublicationStatus::Published);
});

it('rejects unauthenticated redact', function () {
    $publication = Publication::factory()->create();

    $this->json('POST', route('admin.api.passports.redact', $publication->id), ['reason' => 'x'], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
