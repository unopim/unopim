<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\Admin\DataGrids\Catalog\ProductDataGrid;
use Webkul\Admin\Jobs\ExportDataGridSelection;
use Webkul\Core\Facades\ElasticSearch;
use Webkul\Product\Contracts\TemplateRenderer;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

uses(InteractsWithTemplates::class);

dataset('quick template export formats', ['csv', 'xlsx']);

beforeEach(function () {
    config(['elasticsearch.enabled' => false]);

    Storage::fake('private');

    $this->loginAsAdmin();

    $this->channel = $this->templateChannel(['EUR', 'USD'], ['en_US', 'de_DE']);

    $color = $this->variableAttribute('tpl_qexp_color', 'select');

    $this->attributeOption($color, 'blue', ['en_US' => 'Blue', 'de_DE' => 'Blau']);

    $this->variableAttribute('tpl_qexp_brand');
    $this->variableAttribute('tpl_qexp_formula');
    $this->variableAttribute('tpl_qexp_notes');
    $this->variableAttribute('tpl_qexp_name', 'text', ['value_per_locale' => 1]);
    $this->variableAttribute('tpl_qexp_tagline', 'text', ['value_per_channel' => 1]);
    $this->variableAttribute('tpl_qexp_price', 'price');
    $this->variableAttribute('tpl_qexp_title', 'text', ['enable_templates' => 1]);
    $this->variableAttribute('tpl_qexp_headline', 'text', ['enable_templates' => 1, 'value_per_locale' => 1, 'value_per_channel' => 1]);
    $this->templateAttribute('tpl_qexp_description', ['enable_wysiwyg' => 1]);

    $this->names = [
        'en_US' => ['tpl_qexp_name' => 'Sunglasses'],
        'de_DE' => ['tpl_qexp_name' => 'Sonnenbrille'],
    ];
});

function quickTemplateFileRows(string $path, string $format): array
{
    if ($format === 'xlsx') {
        $lines = IOFactory::load($path)->getActiveSheet()->toArray(null, false, false);
    } else {
        $lines = [];

        $handle = fopen($path, 'r');

        while (($line = fgetcsv($handle, escape: '')) !== false) {
            $lines[] = $line;
        }

        fclose($handle);
    }

    $header = array_shift($lines);

    $rows = [];

    foreach ($lines as $line) {
        $row = array_combine($header, array_pad(array_map(fn ($cell): string => (string) $cell, $line), count($header), ''));

        $rows[$row['sku']][$row['channel']][$row['locale']] = $row;
    }

    return $rows;
}

function quickTemplateDownloadRows(array $productIds): array
{
    $response = test()->post(route('admin.catalog.products.quick-export'), [
        'export'     => 1,
        'format'     => 'csv',
        'productIds' => $productIds,
    ])->assertOk()
        ->assertDownload();

    return quickTemplateFileRows($response->baseResponse->getFile()->getPathname(), 'csv');
}

function quickTemplateQueuedRows(string $skuFilter, string $format): array
{
    (new ExportDataGridSelection(
        ProductDataGrid::class,
        ['filters' => ['sku' => [$skuFilter]]],
        $format,
        auth()->guard('admin')->id(),
        'admin::app.catalog.products.index.datagrid.select-all.export',
        'admin.catalog.products.quick-export.download',
    ))->handle();

    $file = collect(Storage::disk('private')->allFiles(ExportDataGridSelection::DIRECTORY))->last();

    return quickTemplateFileRows(Storage::disk('private')->path($file), $format);
}

it('downloads rendered templates for every channel and locale row and keeps the stored sources', function () {
    $product = Product::factory()->create(['values' => [
        'common' => [
            'tpl_qexp_brand'       => 'Acme',
            'tpl_qexp_formula'     => '=HYPERLINK("x")',
            'tpl_qexp_notes'       => 'Plain {{ tpl_qexp_brand }}',
            'tpl_qexp_price'       => ['EUR' => '110', 'USD' => '120'],
            'tpl_qexp_title'       => '{{ tpl_qexp_name }} by {{ tpl_qexp_brand | upper }} for {{ tpl_qexp_price.EUR }}',
            'tpl_qexp_description' => '<p>{{ tpl_qexp_name }} &amp; {{ tpl_qexp_tagline }}</p>',
        ],
        'locale_specific'         => $this->names,
        'channel_specific'        => [$this->channel->code => ['tpl_qexp_tagline' => 'Online only']],
        'channel_locale_specific' => [$this->channel->code => [
            'en_US' => ['tpl_qexp_headline' => '{{ tpl_qexp_formula }}'],
            'de_DE' => ['tpl_qexp_headline' => 'Nur {{ tpl_qexp_tagline | lower }}'],
        ]],
    ]]);

    $stored = $product->fresh()->values;

    $rows = quickTemplateDownloadRows([$product->id])[$product->sku][$this->channel->code];

    expect($rows)->toHaveCount(2)
        ->and($rows['en_US']['tpl_qexp_title'])->toBe('Sunglasses by ACME for 110 EUR')
        ->and($rows['de_DE']['tpl_qexp_title'])->toBe('Sonnenbrille by ACME for 110 EUR')
        ->and($rows['en_US']['tpl_qexp_headline'])->toBe('\'=HYPERLINK("x")\'')
        ->and($rows['de_DE']['tpl_qexp_headline'])->toBe('Nur online only')
        ->and($rows['en_US']['tpl_qexp_description'])->toBe('<p>Sunglasses &amp; Online only</p>')
        ->and($rows['de_DE']['tpl_qexp_description'])->toBe('<p>Sonnenbrille &amp; Online only</p>')
        ->and($rows['en_US']['tpl_qexp_notes'])->toBe('Plain {{ tpl_qexp_brand }}')
        ->and($rows['en_US']['tpl_qexp_price (EUR)'])->toBe('110')
        ->and($rows['en_US'])->not->toHaveKey('parent_id')
        ->and($product->fresh()->values)->toBe($stored);
});

it('writes rendered templates to the queued select-all export file', function (string $format) {
    $product = Product::factory()->create([
        'sku'    => 'QTPL-'.$format,
        'values' => [
            'common'          => ['tpl_qexp_brand' => 'Acme', 'tpl_qexp_title' => '{{ tpl_qexp_name }} by {{ tpl_qexp_brand }}'],
            'locale_specific' => $this->names,
        ],
    ]);

    $rows = quickTemplateQueuedRows('QTPL-', $format)[$product->sku][$this->channel->code];

    expect($rows['en_US']['tpl_qexp_title'])->toBe('Sunglasses by Acme')
        ->and($rows['de_DE']['tpl_qexp_title'])->toBe('Sonnenbrille by Acme');
})->with('quick template export formats');

it('renders templates in quick exports when Elasticsearch is enabled', function () {
    $product = Product::factory()->create([
        'sku'    => 'QTPL-ES',
        'values' => [
            'common'          => ['tpl_qexp_brand' => 'Acme', 'tpl_qexp_title' => '{{ tpl_qexp_name }} by {{ tpl_qexp_brand }}'],
            'locale_specific' => $this->names,
        ],
    ]);

    config([
        'elasticsearch.enabled'                     => true,
        'elasticsearch.prefix'                      => 'testing',
        'elasticsearch.connection'                  => 'default',
        'elasticsearch.connections.default.hosts.0' => 'testhost:9200',
    ]);

    ElasticSearch::shouldReceive('makeConnection')
        ->andReturn(Mockery::mock('Webkul\ElasticSearch\Client\Fake\FakeElasticClient'));

    ElasticSearch::shouldReceive('search')
        ->once()
        ->withArgs(fn (array $args): bool => $args['index'] === 'testing_products')
        ->andReturn(['hits' => ['hits' => [['_id' => (string) $product->id, 'sort' => [$product->id]]]]]);

    $downloaded = quickTemplateDownloadRows([$product->id])[$product->sku][$this->channel->code];

    $queued = quickTemplateQueuedRows('QTPL-ES', 'csv')[$product->sku][$this->channel->code];

    expect($downloaded['en_US']['tpl_qexp_title'])->toBe('Sunglasses by Acme')
        ->and($downloaded['de_DE']['tpl_qexp_title'])->toBe('Sonnenbrille by Acme')
        ->and($queued['en_US']['tpl_qexp_title'])->toBe('Sunglasses by Acme')
        ->and($queued['de_DE']['tpl_qexp_title'])->toBe('Sonnenbrille by Acme');
});

it('renders a variant template with values inherited from its parent while exporting own values only', function () {
    $parent = Product::factory()->configurable()->create(['values' => [
        'common' => [
            'tpl_qexp_brand' => 'Acme',
            'tpl_qexp_title' => '{{ tpl_qexp_brand }}!',
        ],
    ]]);

    $variant = Product::factory()->create([
        'parent_id' => $parent->id,
        'values'    => ['common' => [
            'tpl_qexp_color' => 'blue',
            'tpl_qexp_title' => '{{ tpl_qexp_brand }} {{ tpl_qexp_color }}',
        ]],
    ]);

    $storedVariant = $variant->fresh()->values;

    $rows = quickTemplateDownloadRows([$parent->id, $variant->id]);

    $variantRows = $rows[$variant->sku][$this->channel->code];

    expect($variantRows['en_US']['tpl_qexp_title'])->toBe('Acme Blue')
        ->and($variantRows['de_DE']['tpl_qexp_title'])->toBe('Acme Blau')
        ->and($variantRows['en_US']['tpl_qexp_brand'])->toBe('')
        ->and($rows[$parent->sku][$this->channel->code]['en_US']['tpl_qexp_title'])->toBe('Acme!')
        ->and($variant->fresh()->values)->toBe($storedVariant);
});

it('empties missing or unknown references and keeps the source of a broken template', function () {
    $product = Product::factory()->create(['values' => [
        'common'                  => ['tpl_qexp_title' => 'Tag: {{ tpl_qexp_tagline }}{{ tpl_qexp_unknown }}.'],
        'channel_locale_specific' => [$this->channel->code => [
            'en_US' => ['tpl_qexp_headline' => 'Hi {{ tpl_qexp_name + 1 }}'],
        ]],
    ]]);

    $row = quickTemplateDownloadRows([$product->id])[$product->sku][$this->channel->code]['en_US'];

    expect($row['tpl_qexp_title'])->toBe('Tag: .')
        ->and($row['tpl_qexp_headline'])->toBe('Hi {{ tpl_qexp_name + 1 }}');
});

it('skips the template renderer when no exported product holds a template', function () {
    $this->mock(TemplateRenderer::class, fn ($mock) => $mock->shouldNotReceive('render'));

    $product = Product::factory()->create(['values' => [
        'common'          => ['tpl_qexp_brand' => 'Acme'],
        'locale_specific' => $this->names,
    ]]);

    $row = quickTemplateDownloadRows([$product->id])[$product->sku][$this->channel->code]['de_DE'];

    expect($row['tpl_qexp_brand'])->toBe('Acme')
        ->and($row['tpl_qexp_name'])->toBe('Sonnenbrille');
});
