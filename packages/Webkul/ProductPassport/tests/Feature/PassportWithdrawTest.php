<?php

use Webkul\ProductPassport\DataGrids\Catalog\PublicationDataGrid;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Services\Publisher;

it('withdraws in place so the grid can flash the success message', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    $this->loginWithPermissions('all');

    $response = $this->postJson(route('admin.catalog.passports.withdraw', $publication->id))
        ->assertOk()
        ->assertJsonPath('message', trans('passport::app.publications.withdrawn'));

    expect($response->json())->not->toHaveKey('redirect_url');

    expect($publication->refresh()->status)->toBe(PublicationStatus::Withdrawn);
});

it('offers the withdraw action on a published passport but not on a withdrawn one', function (): void {
    $version = $this->publishedPassportFixture();

    $this->loginWithPermissions('all');

    $grid = resolve(PublicationDataGrid::class);
    $grid->prepareActions();

    $withdraw = collect($grid->getActions())->firstWhere('index', 'withdraw');

    $published = (object) ['id' => $version->publication->id, 'status_code' => PublicationStatus::Published->value];
    $withdrawn = (object) ['id' => $version->publication->id, 'status_code' => PublicationStatus::Withdrawn->value];

    expect(($withdraw->condition)($published))->toBeTrue()
        ->and(($withdraw->condition)($withdrawn))->toBeFalse();
});

it('keeps the action-only status alias out of the export', function (): void {
    $this->publishedPassportFixture();

    $grid = resolve(PublicationDataGrid::class);
    $grid->setQueryBuilder();

    $row = (array) collect($grid->getExportableData())->first();

    expect($row)->not->toHaveKey('status_code')
        ->and($row)->toHaveKey('publication_status');
});

it('refuses to withdraw a redacted passport so redaction stays one-way', function (): void {
    $publication = $this->publishedPassportFixture()->publication;

    $admin = $this->loginWithPermissions('all');

    resolve(Publisher::class)->redactAll($publication, $admin->id, 'gdpr request');

    $this->postJson(route('admin.catalog.passports.withdraw', $publication->id))
        ->assertStatus(422)
        ->assertJsonPath('message', trans('passport::app.publications.withdraw-invalid'));

    expect($publication->refresh()->status)->toBe(PublicationStatus::Redacted);
});
