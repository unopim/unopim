<?php

use Webkul\Publication\Models\PublicationCarrierIssuanceProxy;

it('issues a carrier for a release, returns the svg and records the issuance', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $this->loginWithPermissions('all');

    $response = $this->post(route('admin.catalog.passports.issue_carrier', [$publication->id, 1]))
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->assertHeader('Content-Disposition', 'attachment; filename="passport-'.$publication->uuid.'-r1.svg"');

    expect($response->getContent())->toContain('<svg');

    $issuance = PublicationCarrierIssuanceProxy::modelClass()::query()->where('publication_id', $publication->id)->firstOrFail();

    expect($issuance->release_id)->toBe($version->release_id)
        ->and($issuance->target)->toEndWith('/p/'.$publication->uuid.'/r/1')
        ->and($issuance->issued_by_id)->not->toBeNull();
});

it('404s issuance for a release that does not exist', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    $this->loginWithPermissions('all');

    $this->post(route('admin.catalog.passports.issue_carrier', [$publication->id, 9]))->assertNotFound();

    expect(PublicationCarrierIssuanceProxy::modelClass()::query()->where('publication_id', $publication->id)->exists())->toBeFalse();
});

it('lists releases and issued carriers on the versions page', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.versions', $publication->id))
        ->assertOk()
        ->assertSee(trans('passport::app.publications.releases.title'))
        ->assertSee(trans('passport::app.publications.carrier.issue'))
        ->assertSee(trans('passport::app.publications.carrier.none'));

    $this->post(route('admin.catalog.passports.issue_carrier', [$publication->id, 1]))->assertOk();

    $this->get(route('admin.catalog.passports.versions', $publication->id))
        ->assertOk()
        ->assertSee('/p/'.$publication->uuid.'/r/1')
        ->assertDontSee(trans('passport::app.publications.carrier.none'));
});
