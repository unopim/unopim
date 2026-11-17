<?php

use Illuminate\Support\Facades\File;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

uses(InteractsWithTemplates::class);

dataset('template export formats', ['Csv', 'Xlsx']);

beforeEach(function () {
    $this->loginAsAdmin();

    (new ReflectionProperty(Exporter::class, 'staticInitCache'))->setValue(null, null);

    $this->channel = $this->templateChannel(['EUR', 'USD'], ['en_US', 'de_DE']);

    $color = $this->variableAttribute('tpl_exp_color', 'select');

    $this->attributeOption($color, 'blue', ['en_US' => 'Blue', 'de_DE' => 'Blau']);

    $this->variableAttribute('tpl_exp_brand');
    $this->variableAttribute('tpl_exp_formula');
    $this->variableAttribute('tpl_exp_notes');
    $this->variableAttribute('tpl_exp_name', 'text', ['value_per_locale' => 1]);
    $this->variableAttribute('tpl_exp_tagline', 'text', ['value_per_channel' => 1]);
    $this->variableAttribute('tpl_exp_price', 'price');
    $this->variableAttribute('tpl_exp_released', 'date');
    $this->variableAttribute('tpl_exp_title', 'text', ['enable_templates' => 1]);
    $this->variableAttribute('tpl_exp_headline', 'text', ['enable_templates' => 1, 'value_per_locale' => 1, 'value_per_channel' => 1]);
    $this->templateAttribute('tpl_exp_description', ['enable_wysiwyg' => 1]);

    $this->filters = ['channels' => [$this->channel->code], 'locales' => ['en_US', 'de_DE']];

    $this->names = [
        'en_US' => ['tpl_exp_name' => 'Sunglasses'],
        'de_DE' => ['tpl_exp_name' => 'Sonnenbrille'],
    ];
});

function templateExportRows(array $products, array $filters): array
{
    $jobInstance = JobInstances::create([
        'code'                => 'tpl_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv', ...$filters],
    ]);

    $jobTrack = JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $exporter->prepareProducts(
        new JobTrackBatch(['data' => array_map(fn (Product $product): array => ['id' => $product->id], $products)]),
        null
    );

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

function templateExportFile(array $rows, string $format): array
{
    $buffer = app(FlatItemBuffer::class)->initialize(
        'exports/template-export-test-'.uniqid(),
        'products.'.strtolower($format),
        ['type' => $format, 'fieldDelimiter' => ',', 'fieldEnclosure' => '"', 'shouldAddBOM' => false]
    );

    $buffer->addData($rows);
    $buffer->writerClose();

    $path = $buffer->getFilePath()->getLocalPath();

    $reader = $format === 'Csv' ? new CsvReader : new XlsxReader;
    $reader->open($path);

    $lines = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $lines[] = array_map(fn ($cell): string => (string) $cell->getValue(), $row->getCells());
        }

        break;
    }

    $reader->close();

    File::deleteDirectory(dirname($path));

    $header = array_shift($lines);

    return array_map(fn (array $line): array => array_combine($header, array_pad($line, count($header), '')), $lines);
}

function templateRowsBySku(array $rows): array
{
    $keyed = [];

    foreach ($rows as $row) {
        $keyed[$row['sku']][$row['locale']] = $row;
    }

    return $keyed;
}

it('writes rendered templates for every channel and locale row of the file', function (string $format) {
    $product = Product::factory()->create(['values' => [
        'common' => [
            'tpl_exp_brand'       => 'Acme',
            'tpl_exp_formula'     => '=HYPERLINK("x")',
            'tpl_exp_notes'       => 'Plain {{ tpl_exp_brand }}',
            'tpl_exp_title'       => '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}',
            'tpl_exp_description' => '<p>{{ tpl_exp_name }} &amp; {{ tpl_exp_tagline }}</p>',
        ],
        'locale_specific'         => $this->names,
        'channel_specific'        => [$this->channel->code => ['tpl_exp_tagline' => 'Online only']],
        'channel_locale_specific' => [$this->channel->code => [
            'en_US' => ['tpl_exp_headline' => '{{ tpl_exp_formula }}'],
            'de_DE' => ['tpl_exp_headline' => 'Nur {{ tpl_exp_tagline | lower }}'],
        ]],
    ]]);

    $rows = templateRowsBySku(templateExportFile(templateExportRows([$product], $this->filters), $format))[$product->sku];

    expect($rows)->toHaveCount(2)
        ->and($rows['en_US']['channel'])->toBe($this->channel->code)
        ->and($rows['en_US']['tpl_exp_title'])->toBe('Sunglasses by ACME')
        ->and($rows['de_DE']['tpl_exp_title'])->toBe('Sonnenbrille by ACME')
        ->and($rows['en_US']['tpl_exp_headline'])->toBe('\'=HYPERLINK("x")\'')
        ->and($rows['de_DE']['tpl_exp_headline'])->toBe('Nur online only')
        ->and($rows['en_US']['tpl_exp_description'])->toBe('<p>Sunglasses &amp; Online only</p>')
        ->and($rows['de_DE']['tpl_exp_description'])->toBe('<p>Sonnenbrille &amp; Online only</p>')
        ->and($rows['en_US']['tpl_exp_notes'])->toBe('Plain {{ tpl_exp_brand }}');
})->with('template export formats');

it('renders a template inherited from the parent with the variant values and keeps stored sources', function () {
    $parent = Product::factory()->configurable()->create(['values' => [
        'common' => [
            'tpl_exp_brand' => 'Acme',
            'tpl_exp_title' => '{{ tpl_exp_brand }} {{ tpl_exp_color }}',
        ],
    ]]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => ['tpl_exp_color' => 'blue']],
    ]);

    $storedParent = $parent->fresh()->values;
    $storedVariant = $variant->fresh()->values;

    $rows = templateRowsBySku(templateExportRows([$parent, $variant], $this->filters));

    expect($rows[$variant->sku]['en_US']['tpl_exp_title'])->toBe('Acme Blue')
        ->and($rows[$variant->sku]['de_DE']['tpl_exp_title'])->toBe('Acme Blau')
        ->and($rows[$parent->sku]['en_US']['tpl_exp_title'])->toBe('Acme ')
        ->and($parent->fresh()->values)->toBe($storedParent)
        ->and($variant->fresh()->values)->toBe($storedVariant);
});

it('keeps channel currencies and stored dates inside templates whatever the profile output options', function () {
    $product = Product::factory()->create(['values' => ['common' => [
        'tpl_exp_price'    => ['EUR' => '110', 'USD' => '120'],
        'tpl_exp_released' => '2026-11-13',
        'tpl_exp_title'    => "{{ tpl_exp_price }} | {{ tpl_exp_price.EUR }} | {{ tpl_exp_released }} | {{ tpl_exp_released | format_date('d/m/Y') }}",
    ]]]);

    $row = templateRowsBySku(templateExportRows([$product], [
        ...$this->filters,
        'currencies'  => ['EUR'],
        'date_format' => 'Y.m.d',
    ]))[$product->sku]['en_US'];

    expect($row['tpl_exp_title'])->toBe('110 EUR, 120 USD | 110 EUR | 2026-11-13 | 13/11/2026')
        ->and($row['tpl_exp_released'])->toBe('2026.11.13')
        ->and($row)->toHaveKey('tpl_exp_price (EUR)')
        ->and($row)->not->toHaveKey('tpl_exp_price (USD)');
});

it('empties missing or unknown references and keeps the source of a broken template', function () {
    $product = Product::factory()->create(['values' => [
        'common'                  => ['tpl_exp_title' => 'Tag: {{ tpl_exp_tagline }}{{ tpl_exp_unknown }}.'],
        'channel_locale_specific' => [$this->channel->code => [
            'en_US' => ['tpl_exp_headline' => 'Hi {{ tpl_exp_name + 1 }}'],
        ]],
    ]]);

    $row = templateRowsBySku(templateExportRows([$product], $this->filters))[$product->sku]['en_US'];

    expect($row['tpl_exp_title'])->toBe('Tag: .')
        ->and($row['tpl_exp_headline'])->toBe('Hi {{ tpl_exp_name + 1 }}');
});

it('resolves referenced attributes that the profile does not export', function () {
    $product = Product::factory()->create(['values' => [
        'common'          => ['tpl_exp_brand' => 'Acme', 'tpl_exp_title' => '{{ tpl_exp_name }} by {{ tpl_exp_brand }}'],
        'locale_specific' => $this->names,
    ]]);

    $row = templateRowsBySku(templateExportRows([$product], [...$this->filters, 'attributes' => ['tpl_exp_title']]))[$product->sku]['de_DE'];

    expect($row['tpl_exp_title'])->toBe('Sonnenbrille by Acme')
        ->and($row)->not->toHaveKey('tpl_exp_brand')
        ->and($row)->not->toHaveKey('tpl_exp_name');
});
