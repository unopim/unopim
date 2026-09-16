<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

function mediaExportJobTrack(): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'media_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);
}

function exportMediaRows(array $products): array
{
    $jobTrack = mediaExportJobTrack();

    (new ReflectionProperty(Exporter::class, 'staticInitCache'))->setValue(null, null);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $batch = new JobTrackBatch(['data' => array_map(fn (Product $product) => ['id' => $product->id], $products)]);

    $exporter->prepareProducts($batch, null);

    $buffer->rewind();

    $rows = [];

    while ($buffer->valid()) {
        foreach ($buffer->current() as $row) {
            $rows[] = $row;
        }

        $buffer->next();
    }

    return $rows;
}

function mediaImportJobTrack(): JobTrack
{
    return JobTrack::factory()->create(['action' => Import::ACTION_APPEND]);
}

function freshMediaImporter(): Importer
{
    foreach (['staticInitCache', 'staticInitCacheJobId'] as $property) {
        $reflected = new ReflectionProperty(Importer::class, $property);
        $reflected->setValue(null, null);
    }

    return resolve(Importer::class);
}

function productWithReplacedImage(): array
{
    $attribute = Attribute::factory()->create(['code' => 'media_rt_'.Str::random(8), 'type' => 'image']);

    $product = seedRequiredProductValues(Product::factory()->simple()->create());

    $product->attribute_family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);

    $uri = route('admin.catalog.products.update', $product->id);

    submitMultipartForm($uri, 'PUT', ['sku' => $product->sku], [
        'values' => ['common' => [$attribute->code => [UploadedFile::fake()->image('exported-first.jpg')]]],
    ]);

    submitMultipartForm($uri, 'PUT', ['sku' => $product->sku, 'values' => ['common' => [$attribute->code => '']]], [
        'values' => ['common' => [$attribute->code => [UploadedFile::fake()->image('exported-second.jpg')]]],
    ]);

    return [$product->refresh(), $attribute->code];
}

describe('product media export', function () {
    it('exports the image path that the last save stored', function () {
        $this->loginAsAdmin();

        Storage::fake();

        [$product, $code] = productWithReplacedImage();

        $stored = $product->values['common'][$code];

        expect($stored)->toContain('exported-second.jpg');

        $rows = exportMediaRows([$product]);

        $row = collect($rows)->firstWhere('sku', $product->sku);

        expect($row)->not->toBeNull()
            ->and($row[$code] ?? null)->toBe($stored);
    });

    it('exports an empty media column once the image is removed', function () {
        $this->loginAsAdmin();

        Storage::fake();

        [$product, $code] = productWithReplacedImage();

        submitMultipartForm(
            route('admin.catalog.products.update', $product->id),
            'PUT',
            ['sku' => $product->sku, 'values' => ['common' => [$code => '']]]
        );

        $product->refresh();

        $rows = exportMediaRows([$product]);

        $row = collect($rows)->firstWhere('sku', $product->sku);

        expect($row[$code] ?? '')->toBe('');
    });
});

describe('product media import', function () {
    it('imports an exported media row into a new product and relocates the file', function () {
        $this->loginAsAdmin();

        $jobTrack = mediaImportJobTrack();

        Storage::fake('public');

        [$product, $code] = productWithReplacedImage();

        $rows = exportMediaRows([$product]);

        $row = collect($rows)->firstWhere('sku', $product->sku);

        $importedSku = 'imported-'.Str::random(8);

        $row['sku'] = $importedSku;

        $importer = freshMediaImporter();

        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        expect($importer->validateRow($row, 1))->toBeTrue();

        $batch = JobTrackBatch::factory()->create([
            'data'         => [$row],
            'job_track_id' => $jobTrack->id,
        ]);

        $importer->importBatch($batch);

        $imported = Product::where('sku', $importedSku)->first();

        expect($imported)->not->toBeNull();

        $importedPath = $imported->values['common'][$code] ?? null;

        expect($importedPath)->not->toBeNull()
            ->and($importedPath)->toContain('exported-second.jpg')
            ->and($importedPath)->toStartWith('product/'.$imported->id.'/'.$code.'/')
            ->and(Storage::disk('public')->exists($importedPath))->toBeTrue();
    });

    it('updates the media value of an existing product on re-import', function () {
        $this->loginAsAdmin();

        $jobTrack = mediaImportJobTrack();

        Storage::fake('public');

        [$product, $code] = productWithReplacedImage();

        $replacement = 'import-images/product-1/product/99/'.$code.'/aHashedFolder/reimported.jpg';

        Storage::disk('public')->put($replacement, 'binary');

        $rows = exportMediaRows([$product]);

        $row = collect($rows)->firstWhere('sku', $product->sku);

        $row[$code] = $replacement;

        $importer = freshMediaImporter();

        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        expect($importer->validateRow($row, 1))->toBeTrue();

        $batch = JobTrackBatch::factory()->create([
            'data'         => [$row],
            'job_track_id' => $jobTrack->id,
        ]);

        $importer->importBatch($batch);

        $product->refresh();

        $stored = $product->values['common'][$code] ?? null;

        expect($stored)->toContain('reimported.jpg')
            ->and($stored)->toStartWith('product/'.$product->id.'/'.$code.'/')
            ->and(Storage::disk('public')->exists($stored))->toBeTrue();
    });
});
