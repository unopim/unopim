<?php

use Illuminate\Filesystem\FilesystemAdapter;
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

it('keeps a plain attribute field escaped as text, not rich text', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes();

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('code', 'dpp_material_composition');

    expect($field['is_rich_text'])->toBeFalse()
        ->and($field['value_text'])->toBe($field['value']);
});

it('sanitizes a WYSIWYG attribute field to a safe HTML subset and flags it as rich text', function (): void {
    [$product, $context] = $this->productWithRichTextPassportField(
        '<p onclick="steal()">Hand <b>wash</b> only.</p><script>alert(1)</script><ul><li>Do not bleach</li></ul>'
    );

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('code', 'dpp_care_instructions');

    expect($field['is_rich_text'])->toBeTrue()
        ->and($field['value'])->toContain('<p>Hand <b>wash</b> only.</p>')
        ->and($field['value'])->toContain('<ul><li>Do not bleach</li></ul>')
        ->and($field['value'])->not->toContain('<script>')
        ->and($field['value'])->not->toContain('onclick');
});

it('strips a rich-text field down to a clean plain-text sibling for non-HTML consumers', function (): void {
    [$product, $context] = $this->productWithRichTextPassportField(
        '<p>Hand wash only.</p><p>Line two.</p><ul><li>Do not bleach</li><li>No tumble dry</li></ul>'
    );

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('code', 'dpp_care_instructions');

    expect($field['value_text'])
        ->not->toContain('<')
        ->toContain('Hand wash only.')
        ->toContain('Line two.')
        ->toContain('Do not bleach')
        ->toContain('No tumble dry');
});

it('never puts raw markup behind a non-rich-text field, even when the value looks like HTML', function (): void {
    [$product, $context] = $this->makeProductWithValues(['country' => '<b>Germany</b>']);

    $payload = resolve(PassportPayloadBuilder::class)->build($product, $context);

    $field = collect($payload['sections'][0]['fields'])->firstWhere('code', 'dpp_country_of_origin');

    expect($field['is_rich_text'])->toBeFalse()
        ->and($field['value'])->toBe('<b>Germany</b>');
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

it('hashes and copies a multi-megabyte document intact', function (): void {
    [$product, $context, $sourcePath] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $contents = random_bytes(2 * 1024 * 1024);

    Storage::disk(config('filesystems.default'))->put($sourcePath, $contents);

    $document = resolve(PassportPayloadBuilder::class)->build($product, $context)['documents'][0];

    expect($document['sha256'])->toBe(hash('sha256', $contents))
        ->and(Storage::disk(config('publication.asset_disk'))->get($document['path']))->toBe($contents);
});

it('stamps no path when the source document cannot be read', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $disk = Storage::disk(config('filesystems.default'));

    Storage::set(config('filesystems.default'), new class($disk->getDriver(), $disk->getAdapter(), $disk->getConfig()) extends FilesystemAdapter
    {
        public function readStream($path)
        {
            return null;
        }
    });

    $document = resolve(PassportPayloadBuilder::class)->build($product, $context)['documents'][0];

    expect($document)->not->toHaveKey('path')
        ->and($document)->not->toHaveKey('sha256')
        ->and(Storage::disk(config('publication.asset_disk'))->exists('publication/'.$context->uuid))->toBeFalse();
});

it('stamps no path when the asset disk refuses the write', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $disk = Storage::disk(config('publication.asset_disk'));

    Storage::set(config('publication.asset_disk'), new class($disk->getDriver(), $disk->getAdapter(), $disk->getConfig()) extends FilesystemAdapter
    {
        public function writeStream($path, $resource, array $options = [])
        {
            return false;
        }
    });

    $document = resolve(PassportPayloadBuilder::class)->build($product, $context)['documents'][0];

    expect($document)->not->toHaveKey('path')
        ->and($document)->not->toHaveKey('sha256');
});

it('keeps the copied document when the asset disk closes the stream it was given', function (): void {
    [$product, $context] = $this->productWithSecretAndDppAttributes(withDocument: true);

    $disk = Storage::disk(config('publication.asset_disk'));

    Storage::set(config('publication.asset_disk'), new class($disk->getDriver(), $disk->getAdapter(), $disk->getConfig()) extends FilesystemAdapter
    {
        public function writeStream($path, $resource, array $options = [])
        {
            $written = parent::writeStream($path, $resource, $options);

            fclose($resource);

            return $written;
        }
    });

    $document = resolve(PassportPayloadBuilder::class)->build($product, $context)['documents'][0];

    expect($document)->toHaveKey('path')
        ->and(Storage::disk(config('publication.asset_disk'))->get($document['path']))->toBe('%PDF-1.4 stub');
});
