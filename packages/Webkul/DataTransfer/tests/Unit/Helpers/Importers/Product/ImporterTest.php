<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Helpers\Sources\AbstractSource;
use Webkul\DataTransfer\Helpers\Sources\CSV;
use Webkul\DataTransfer\Helpers\Sources\Excel;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

/**
 * Build a real configurable family with one super attribute + options, then
 * strip the seeded products so the import path has to recreate them.
 *
 * @return array{familyCode: string, attributeCode: string, optionCode: string}
 */
function configurableImportFixture(): array
{
    $blueprint = Product::factory()->configurable()->withVariantProduct()->create();

    $superAttribute = $blueprint->super_attributes->first();

    $fixture = [
        'familyCode'    => $blueprint->attribute_family->code,
        'attributeCode' => $superAttribute->code,
        'optionCode'    => $superAttribute->options->first()->code,
    ];

    Product::where('parent_id', $blueprint->id)->delete();
    $blueprint->super_attributes()->detach();
    $blueprint->delete();

    return $fixture;
}

function runImportBatch(array $rows): void
{
    $jobTrack = JobTrack::factory()->create(['type' => 'import', 'action' => 'append']);

    $batch = JobTrackBatch::factory()->create([
        'job_track_id' => $jobTrack->id,
        'data'         => $rows,
    ]);

    app(Importer::class)->setImport($jobTrack)->importBatch($batch->refresh());
}

/**
 * @return array<int, array<string, mixed>>
 */
function readSourceRows(AbstractSource $source): array
{
    $rows = [];

    $source->rewind();

    while ($source->valid()) {
        $rows[] = $source->current();

        $source->next();
    }

    return $rows;
}

function writeCsvFixture(string $path, array $header, array $rows): void
{
    $lines = array_map(fn (array $row): string => implode(',', $row), array_merge([$header], $rows));

    Storage::disk('private')->put($path, implode("\n", $lines)."\n");
}

function writeXlsxFixture(string $path, array $header, array $rows): void
{
    Storage::disk('private')->put($path, '');

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray(array_merge([$header], $rows), null, 'A1');

    (new XlsxWriter($spreadsheet))->save(Storage::disk('private')->path($path));

    $spreadsheet->disconnectWorksheets();
}

it('persists the configurable super_attributes pivot on import', function () {
    config(['elasticsearch.enabled' => false]);

    $fixture = configurableImportFixture();

    runImportBatch([
        [
            'sku'                     => 'IMP-CONFIG-PARENT',
            'type'                    => 'configurable',
            'attribute_family'        => $fixture['familyCode'],
            'status'                  => 'true',
            'configurable_attributes' => $fixture['attributeCode'],
        ],
    ]);

    $parent = Product::where('sku', 'IMP-CONFIG-PARENT')->first();

    expect($parent)->not->toBeNull()
        ->and($parent->type)->toBe('configurable')
        ->and($parent->super_attributes->pluck('code')->all())
        ->toContain($fixture['attributeCode']);
});

it('links a simple child variant to its imported configurable parent', function () {
    config(['elasticsearch.enabled' => false]);

    $fixture = configurableImportFixture();

    runImportBatch([
        [
            'sku'                     => 'IMP-CONFIG-PARENT',
            'type'                    => 'configurable',
            'attribute_family'        => $fixture['familyCode'],
            'status'                  => 'true',
            'configurable_attributes' => $fixture['attributeCode'],
        ],
    ]);

    runImportBatch([
        [
            'sku'                        => 'IMP-CONFIG-CHILD',
            'type'                       => 'simple',
            'attribute_family'           => $fixture['familyCode'],
            'status'                     => 'true',
            'parent'                     => 'IMP-CONFIG-PARENT',
            $fixture['attributeCode']    => $fixture['optionCode'],
        ],
    ]);

    $parent = Product::where('sku', 'IMP-CONFIG-PARENT')->first();
    $child = Product::where('sku', 'IMP-CONFIG-CHILD')->first();

    expect($child)->not->toBeNull()
        ->and($child->parent_id)->toBe($parent->id)
        ->and($child->values['common'][$fixture['attributeCode']] ?? null)->toBe($fixture['optionCode']);
});

dataset('product file formats', [
    'csv'  => ['csv', 'writeCsvFixture', fn (string $path) => new CSV($path, ',')],
    'xlsx' => ['xlsx', 'writeXlsxFixture', fn (string $path) => new Excel($path)],
]);

it('round-trips a configurable parent through the real file reader', function (string $extension, string $writer, Closure $makeSource) {
    config(['elasticsearch.enabled' => false]);

    $fixture = configurableImportFixture();
    $path = 'imports/configurable-'.$extension.'-'.uniqid().'.'.$extension;

    $writer(
        $path,
        ['sku', 'type', 'attribute_family', 'status', 'configurable_attributes'],
        [['RT-'.strtoupper($extension).'-PARENT', 'configurable', $fixture['familyCode'], 'true', $fixture['attributeCode']]],
    );

    $rows = readSourceRows($makeSource($path));

    expect($rows)->toHaveCount(1);

    runImportBatch($rows);

    $parent = Product::where('sku', 'RT-'.strtoupper($extension).'-PARENT')->first();

    expect($parent)->not->toBeNull()
        ->and($parent->type)->toBe('configurable')
        ->and($parent->super_attributes->pluck('code')->all())->toContain($fixture['attributeCode']);

    Storage::disk('private')->delete($path);
})->with('product file formats');

it('exposes the field enclosure under the key the writer factory reads', function () {
    $exporter = app(Exporter::class);

    expect($exporter->getExportParameter())
        ->toHaveKey('fieldEnclosure')
        ->not->toHaveKey('filedEnclosure');
});
