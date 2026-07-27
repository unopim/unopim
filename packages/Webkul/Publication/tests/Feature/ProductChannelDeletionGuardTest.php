<?php

use Webkul\Publication\Models\Publication;

it('blocks deleting a product that still has a publication, without leaking a raw sql error', function (): void {
    $version = $this->publishedPassportFixture();

    $this->loginWithPermissions('all');

    $response = $this->deleteJson(route('admin.catalog.products.delete', $version->publication->product_id));

    $response->assertStatus(500);

    expect($response->json('message'))->not->toContain('SQLSTATE')
        ->and(Publication::whereKey($version->publication->id)->exists())->toBeTrue();
});
