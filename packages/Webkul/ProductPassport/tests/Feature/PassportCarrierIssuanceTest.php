<?php

use Webkul\Publication\Models\PublicationCarrierIssuanceProxy;
use Webkul\Publication\Models\PublicationReleaseProxy;
use Webkul\Publication\Services\Publisher;

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

it('refuses to issue a carrier for a withdrawn or redacted passport and records nothing', function (string $state): void {
    $publication = $this->publishedPassportFixture()->publication;

    $admin = $this->loginWithPermissions('all');

    $publisher = resolve(Publisher::class);

    $state === 'withdrawn'
        ? $publisher->withdraw($publication)
        : $publisher->redactAll($publication, $admin->id, 'gdpr request');

    $this->post(route('admin.catalog.passports.issue_carrier', [$publication->id, 1]))
        ->assertStatus(422);

    expect(PublicationCarrierIssuanceProxy::modelClass()::query()->where('publication_id', $publication->id)->exists())->toBeFalse();

    $this->get(route('admin.catalog.passports.versions', $publication->id))
        ->assertOk()
        ->assertSee(trans('passport::app.publications.releases.title'))
        ->assertDontSee(trans('passport::app.publications.carrier.issue'));
})->with(['withdrawn', 'redacted']);

it('refuses a carrier for a redacted release of a published passport and hides its issue button', function (): void {
    $first = $this->publishedPassportFixture();
    $publication = $first->publication;

    $other = $publication->channel->locales()->where('locales.id', '!=', $first->locale_id)->firstOrFail();

    $product = $publication->product;
    $product->values = array_replace_recursive($product->values, [
        'locale_specific' => [$other->code => ['dpp_material_composition' => 'aluminium']],
    ]);
    $product->save();

    resolve(Publisher::class)->publish($product, $publication->channel, $other, 'dpp');

    $admin = $this->loginWithPermissions('all');

    $first->redact($admin->id, 'erased on request');

    $this->postJson(route('admin.catalog.passports.issue_carrier', [$publication->id, 1]))
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.carrier.release-redacted'));

    expect(PublicationCarrierIssuanceProxy::modelClass()::query()->where('publication_id', $publication->id)->exists())->toBeFalse();

    $this->get(route('admin.catalog.passports.versions', $publication->id))
        ->assertOk()
        ->assertSee(route('admin.catalog.passports.issue_carrier', [$publication->id, 2]))
        ->assertDontSee(route('admin.catalog.passports.issue_carrier', [$publication->id, 1]));

    $this->post(route('admin.catalog.passports.issue_carrier', [$publication->id, 2]))->assertOk();
});

it('pages the releases and the issued carriers on the versions page', function (): void {
    $version = $this->publishedPassportFixture();
    $publication = $version->publication;

    foreach (range(2, 51) as $sequence) {
        PublicationReleaseProxy::modelClass()::factory()->create(['publication_id' => $publication->id, 'sequence' => $sequence]);
    }

    foreach (range(1, 51) as $index) {
        PublicationCarrierIssuanceProxy::modelClass()::factory()->create([
            'publication_id' => $publication->id,
            'release_id'     => $version->release_id,
            'target'         => 'https://example.test/p/'.$publication->uuid.'/r/1?print='.$index,
            'issued_at'      => now()->subMinutes(100 - $index),
        ]);
    }

    $this->loginWithPermissions('all');

    $next = trans('admin::app.components.datagrid.toolbar.pagination.next-page');

    $this->get(route('admin.catalog.passports.versions', $publication->id))
        ->assertOk()
        ->assertSee($next)
        ->assertSee('r/1?print=51')
        ->assertDontSee('r/1?print=1<', false)
        ->assertSee('releases_page=2', false)
        ->assertSee('issuances_page=2', false);

    $this->get(route('admin.catalog.passports.versions', [$publication->id, 'issuances_page' => 2]))
        ->assertOk()
        ->assertSee('r/1?print=1<', false)
        ->assertDontSee('r/1?print=51');
});
