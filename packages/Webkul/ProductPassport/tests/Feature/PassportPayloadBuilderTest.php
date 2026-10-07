<?php

use Illuminate\Support\Facades\Storage;
use Webkul\ProductPassport\Services\PassportPayloadBuilder;

it('includes only attributes from the dpp group, scoped to the product\'s own family', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $codes = collect($payload['sections'])->flatMap(fn (array $s): array => array_column($s['fields'], 'code'));

    expect($codes)->toContain('dpp_material_composition')
        ->and($codes)->not->toContain('internal_cost_price');
});

it('resolves a common-bucket value inherited from a configurable parent', function (): void {
    [$variant, $context] = $this->variantWithInheritedPassportValues();

    $payload = resolve(PassportPayloadBuilder::class)->build($variant, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('code', 'dpp_manufacturer_name');

    expect($field['value'])->toBe('Acme Corp');
});

it('carries the configured economic operator', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $this->setPassportConfig(['operator_name' => 'Acme GmbH']);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    expect($payload['operator']['name'])->toBe('Acme GmbH');
});

it('copies a referenced document onto the asset disk and stamps the final path', function (): void {
    [$product, $context, $sourcePath] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $documentPath = $payload['documents'][0]['path'];

    expect($documentPath)->not->toBe($sourcePath)
        ->and(Storage::disk(config('publication.asset_disk'))->exists($documentPath))->toBeTrue();
});

it('excludes the whole meta key from being treated as content by never placing content there', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    expect(array_keys($payload['meta']))->toEqualCanonicalizing(['uuid', 'url', 'locale', 'channel', 'built_at', 'template']);
});

it('addresses a copied document by its content so a re-issued file lands beside the old one', function (): void {
    [$product, $context, $sourcePath] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $builder = resolve(PassportPayloadBuilder::class);
    $assets = Storage::disk(config('publication.asset_disk'));

    $first = $builder->build($product, $context)['documents'][0];

    Storage::disk(config('filesystems.default'))->put($sourcePath, '%PDF-1.4 re-issued');

    $second = $builder->build($product, $context)['documents'][0];

    expect($second['path'])->not->toBe($first['path'])
        ->and($second['sha256'])->toBe(hash('sha256', '%PDF-1.4 re-issued'))
        ->and($second['sha256'])->not->toBe($first['sha256'])
        ->and(basename($second['path']))->toBe(basename($first['path']))
        ->and($assets->get($first['path']))->toBe('%PDF-1.4 stub')
        ->and($assets->get($second['path']))->toBe('%PDF-1.4 re-issued');
});

it('reuses the same asset path while the document is unchanged', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $builder = resolve(PassportPayloadBuilder::class);

    expect($builder->build($product, $context)['documents'][0]['path'])
        ->toBe($builder->build($product, $context)['documents'][0]['path']);
});
