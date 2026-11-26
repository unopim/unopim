<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Psr\Log\LoggerInterface;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

use function Pest\Laravel\get;
use function Pest\Laravel\postJson;

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

    $this->filters = ['channels' => [$this->channel->code], 'locales' => ['en_US', 'de_DE'], 'render_templates' => '1'];

    $this->names = [
        'en_US' => ['tpl_exp_name' => 'Sunglasses'],
        'de_DE' => ['tpl_exp_name' => 'Sonnenbrille'],
    ];
});

function templateExportRows(array $products, array $filters, ?LoggerInterface $logger = null): array
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

    if ($logger !== null) {
        $exporter->setLogger($logger);
    }

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
            $lines[] = array_map(fn (Cell $cell): string => (string) $cell->getValue(), $row->getCells());
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

function importTemplateExportRows(array $rows): void
{
    foreach (['staticInitCache', 'staticInitCacheJobId'] as $property) {
        (new ReflectionProperty(Importer::class, $property))->setValue(null, null);
    }

    $jobTrack = JobTrack::factory()->create(['action' => Import::ACTION_APPEND]);

    $importer = resolve(Importer::class);
    $importer->setImport($jobTrack);
    $importer->setErrorHelper(app(Error::class));

    foreach ($rows as $index => $row) {
        expect($importer->validateRow($row, $index + 1))->toBeTrue();
    }

    $importer->importBatch(JobTrackBatch::factory()->create([
        'data'         => $rows,
        'job_track_id' => $jobTrack->id,
    ]));
}

function templateExportProfile(mixed $renderTemplates): array
{
    return [
        'code'            => 'tpl_export_'.uniqid(),
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => ['file_format' => 'Csv', 'render_templates' => $renderTemplates],
    ];
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

it('logs each template problem once per batch with a count and a sample row', function () {
    $products = collect([
        Product::factory()->create(['sku' => 'tpl-exp-log-first', 'values' => [
            'common'                  => ['tpl_exp_title' => 'Tag: {{ tpl_exp_unknown }}.'],
            'channel_locale_specific' => [$this->channel->code => [
                'en_US' => ['tpl_exp_headline' => 'Hi {{ tpl_exp_name + 1 }}'],
            ]],
        ]]),
        Product::factory()->create(['sku' => 'tpl-exp-log-second', 'values' => [
            'common' => ['tpl_exp_title' => 'Tag: {{ tpl_exp_unknown }}.'],
        ]]),
    ]);

    $handler = new TestHandler;

    templateExportRows($products->all(), $this->filters, new Logger('export', [$handler]));

    $records = fn (string $message): Collection => collect($handler->getRecords())
        ->filter(fn (LogRecord $record): bool => $record->message === $message)
        ->values();

    $warnings = $records(trans('product::app.templates.messages.unknown-attribute', ['attribute' => 'tpl_exp_unknown']));

    $errors = $records(trans('product::app.templates.messages.invalid-syntax', ['expression' => 'tpl_exp_name + 1']));

    expect($warnings)->toHaveCount(1)
        ->and($warnings->first()->level->getName())->toBe('WARNING')
        ->and(Arr::only($warnings->first()->context, ['attribute', 'count', 'sample_channel']))->toEqual([
            'attribute'      => 'tpl_exp_title',
            'count'          => 4,
            'sample_channel' => $this->channel->code,
        ])
        ->and($warnings->first()->context['sample_sku'])->toBeIn($products->pluck('sku')->all())
        ->and($errors)->toHaveCount(1)
        ->and($errors->first()->level->getName())->toBe('ERROR')
        ->and($errors->first()->context)->toEqual([
            'attribute'      => 'tpl_exp_headline',
            'count'          => 1,
            'sample_sku'     => 'tpl-exp-log-first',
            'sample_channel' => $this->channel->code,
            'sample_locale'  => 'en_US',
        ]);
});

it('reloads the template attributes for every export job', function () {
    $product = Product::factory()->create(['values' => [
        'common'          => ['tpl_exp_title' => 'Hi {{ tpl_exp_name }}'],
        'locale_specific' => $this->names,
    ]]);

    $before = templateRowsBySku(templateExportRows([$product], $this->filters))[$product->sku]['en_US'];

    Attribute::query()->where('code', 'tpl_exp_title')->update(['enable_templates' => false]);

    $after = templateRowsBySku(templateExportRows([$product], $this->filters))[$product->sku]['en_US'];

    expect($before['tpl_exp_title'])->toBe('Hi Sunglasses')
        ->and($after['tpl_exp_title'])->toBe('Hi {{ tpl_exp_name }}');
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

it('writes the stored template sources when the profile turns rendering off', function (string $format) {
    $sources = [
        'tpl_exp_title'       => '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}',
        'tpl_exp_description' => '<p>{{ tpl_exp_name }} &amp; {{ tpl_exp_tagline }}</p>',
    ];

    $product = Product::factory()->create(['values' => [
        'common'                  => ['tpl_exp_brand' => 'Acme', ...$sources],
        'locale_specific'         => $this->names,
        'channel_locale_specific' => [$this->channel->code => [
            'de_DE' => ['tpl_exp_headline' => 'Nur {{ tpl_exp_tagline | lower }}'],
        ]],
    ]]);

    $rows = templateRowsBySku(templateExportFile(
        templateExportRows([$product], [...$this->filters, 'render_templates' => '0']),
        $format
    ))[$product->sku];

    expect($rows['en_US'])->toMatchArray([...$sources, 'tpl_exp_brand' => 'Acme', 'tpl_exp_name' => 'Sunglasses'])
        ->and($rows['de_DE'])->toMatchArray([...$sources, 'tpl_exp_headline' => 'Nur {{ tpl_exp_tagline | lower }}']);
})->with('template export formats');

it('renders templates only when the profile turns rendering on', function (array $option, array $titles) {
    $product = Product::factory()->create(['values' => [
        'common'          => ['tpl_exp_brand' => 'Acme', 'tpl_exp_title' => '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}'],
        'locale_specific' => $this->names,
    ]]);

    $rows = templateRowsBySku(templateExportRows([$product], [...Arr::except($this->filters, 'render_templates'), ...$option]))[$product->sku];

    expect([$rows['en_US']['tpl_exp_title'], $rows['de_DE']['tpl_exp_title']])->toBe($titles);
})->with([
    'option on'     => [['render_templates' => '1'], ['Sunglasses by ACME', 'Sonnenbrille by ACME']],
    'option off'    => [['render_templates' => '0'], array_fill(0, 2, '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}')],
    'older profile' => [[], array_fill(0, 2, '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}')],
]);

it('leaves the render templates option off on a new export profile', function () {
    $field = collect(config('exporters.products.filters.fields'))->firstWhere('name', 'render_templates');

    expect($field)->not->toHaveKey('default');
});

it('stores the render templates option on the export profile and rejects other values', function () {
    $profile = templateExportProfile('0');

    postJson(route('admin.settings.data_transfer.exports.store'), $profile)
        ->assertStatus(302)
        ->assertSessionHas('success');

    postJson(route('admin.settings.data_transfer.exports.store'), templateExportProfile('yes'))
        ->assertJsonValidationErrors(['filters[render_templates]']);

    expect(JobInstances::query()->where('code', $profile['code'])->sole()->filters['render_templates'])->toBe('0');
});

it('shows the render templates option in the output section of the export profile pages', function () {
    $profile = JobInstances::create([
        'code'                => 'tpl_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    get(route('admin.settings.data_transfer.exports.create'))
        ->assertOk()
        ->assertSee('use_labels,render_templates,date_format', false);

    get(route('admin.settings.data_transfer.exports.edit', ['id' => $profile->id]))
        ->assertOk()
        ->assertSee('use_labels,render_templates,date_format', false);
});

it('imports a file exported without rendering back to the same template sources', function (string $format) {
    $codes = ['tpl_exp_brand', 'tpl_exp_name', 'tpl_exp_tagline', 'tpl_exp_title', 'tpl_exp_headline', 'tpl_exp_description'];

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::query()->whereIn('code', $codes)->pluck('id')->all());
    AttributeFamily::factory()->linkStatusAndSkuToFamily($family);

    $product = Product::factory()->create([
        'attribute_family_id' => $family->id,
        'values'              => [
            'common' => [
                'tpl_exp_brand'       => 'Acme',
                'tpl_exp_title'       => '{{ tpl_exp_name }} by {{ tpl_exp_brand | upper }}',
                'tpl_exp_description' => '<p>{{ tpl_exp_name }} for {{ tpl_exp_tagline | lower }}</p>',
            ],
            'locale_specific'         => $this->names,
            'channel_specific'        => [$this->channel->code => ['tpl_exp_tagline' => 'Online only']],
            'channel_locale_specific' => [$this->channel->code => [
                'en_US' => ['tpl_exp_headline' => 'Hi {{ tpl_exp_name | lower }}'],
                'de_DE' => ['tpl_exp_headline' => 'Nur {{ tpl_exp_tagline }}'],
            ]],
        ],
    ]);

    $sku = 'tpl-exp-round-trip-'.uniqid();

    $rows = array_map(
        fn (array $row): array => ['sku' => $sku] + $row,
        templateExportFile(templateExportRows([$product], [...$this->filters, 'attributes' => $codes, 'render_templates' => '0']), $format)
    );

    importTemplateExportRows($rows);

    $source = $product->fresh()->values;
    $imported = Product::query()->where('sku', $sku)->sole()->values;

    expect($imported['common'])->toMatchArray([
        'tpl_exp_title'       => $source['common']['tpl_exp_title'],
        'tpl_exp_description' => $source['common']['tpl_exp_description'],
    ])->and($imported['channel_locale_specific'][$this->channel->code])
        ->toEqual($source['channel_locale_specific'][$this->channel->code]);
})->with('template export formats');
