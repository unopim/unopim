<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Models\Product;
use Webkul\Product\Services\VariantStructureWriter;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;
use Webkul\Product\Validator\Rule\TemplateExpressionRule;

uses(InteractsWithTemplates::class);

dataset('template import formats', ['csv', 'xlsx']);

beforeEach(function () {
    Storage::fake('private');

    $color = $this->variableAttribute('tpl_imp_color', 'select');

    $this->attributeOption($color, 'red');
    $this->attributeOption($color, 'blue');

    $attributes = [
        $color,
        $this->variableAttribute('tpl_imp_name'),
        $this->variableAttribute('tpl_imp_brand'),
        $this->variableAttribute('tpl_imp_note'),
        $this->variableAttribute('tpl_imp_released', 'date'),
        $this->variableAttribute('tpl_imp_title', 'text', ['enable_templates' => 1]),
        $this->variableAttribute('tpl_imp_variant_title', 'text', ['enable_templates' => 1]),
        $this->variableAttribute('tpl_imp_headline', 'text', ['enable_templates' => 1, 'value_per_locale' => 1, 'value_per_channel' => 1]),
        $this->templateAttribute('tpl_imp_description', ['enable_wysiwyg' => 1]),
        $this->templateAttribute('tpl_imp_summary', ['value_per_locale' => 1]),
    ];

    $this->family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($this->family);
    AttributeFamily::factory()->linkAttributesToFamily($this->family, array_map(fn (Attribute $attribute): int => $attribute->id, $attributes));
    AttributeFamily::factory()->linkStatusAndSkuToFamily($this->family);

    $this->channel = $this->templateChannel(['USD'], ['en_US', 'de_DE']);

    $this->row = fn (string $sku, array $values = [], string $locale = 'en_US'): array => [
        'sku'              => $sku,
        'type'             => 'simple',
        'parent'           => '',
        'attribute_family' => $this->family->code,
        'channel'          => $this->channel->code,
        'locale'           => $locale,
        ...$values,
    ];
});

function templateImportSheet(array $rows): array
{
    $columns = array_keys(array_merge(...$rows));

    return [
        $columns,
        ...array_map(fn (array $row): array => array_map(fn (string $column): string => $row[$column] ?? '', $columns), $rows),
    ];
}

function templateImportCsv(array $rows): string
{
    $handle = fopen('php://temp', 'r+');

    foreach (templateImportSheet($rows) as $line) {
        fputcsv($handle, $line, escape: '');
    }

    rewind($handle);

    return (string) stream_get_contents($handle);
}

function templateImportXlsx(array $rows): string
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray(templateImportSheet($rows), null, 'A1', true);

    $file = tempnam(sys_get_temp_dir(), 'tpl-import');

    (new Xlsx($spreadsheet))->save($file);

    $content = (string) file_get_contents($file);

    unlink($file);

    return $content;
}

function importTemplateRows(array $rows, string $format): array
{
    $settings = [
        'type'                  => 'import',
        'action'                => Import::ACTION_APPEND,
        'field_separator'       => ',',
        'file_path'             => 'import/templates-'.Str::uuid().'.'.$format,
        'images_directory_path' => '',
    ];

    Storage::disk('private')->put($settings['file_path'], match ($format) {
        'csv'  => templateImportCsv($rows),
        'xlsx' => templateImportXlsx($rows),
    });

    $jobTrack = JobTrack::factory()->create([
        ...$settings,
        'job_instances_id'    => JobInstances::factory()->create([...$settings, 'code' => 'templates-'.Str::uuid(), 'entity_type' => 'products'])->id,
        'validation_strategy' => Import::VALIDATION_STRATEGY_SKIP_ERRORS,
        'allowed_errors'      => 1000,
        'state'               => Import::STATE_PENDING,
    ]);

    $import = app(Import::class)->setImport($jobTrack)->setLogger(app('log')->channel('single'));

    $import->validate();

    $errors = $import->getErrorHelper()->getAllErrors();

    if ($import->getImport()->state === Import::STATE_VALIDATED) {
        $import->started();

        foreach ($jobTrack->refresh()->batches as $batch) {
            $import->start($batch);
        }
    }

    return $errors;
}

function templateImportLine(string $format, int $dataRow): int
{
    return $format === 'xlsx' ? $dataRow + 1 : $dataRow;
}

function templateImportError(string $column, string $message): array
{
    return ['code' => TemplateExpressionRule::class, 'column' => $column, 'message' => $message];
}

function templateImportValues(string $sku): array
{
    return Product::query()->where('sku', $sku)->sole()->values;
}

function importedTemplateSkus(array $skus): array
{
    return Product::query()->whereIn('sku', $skus)->orderBy('sku')->pluck('sku')->all();
}

it('creates a product that keeps the plain text template source', function (string $format) {
    $errors = importTemplateRows([($this->row)('tpl-desk', [
        'tpl_imp_name'  => 'Desk',
        'tpl_imp_title' => 'Buy {{ tpl_imp_name | upper }} now',
    ])], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-desk')['common'])->toMatchArray([
            'tpl_imp_name'  => 'Desk',
            'tpl_imp_title' => 'Buy {{ tpl_imp_name | upper }} now',
        ]);
})->with('template import formats');

it('keeps a rich text template intact through html purification', function (string $format) {
    $source = "<p>About {{ tpl_imp_name | upper }} from {{ tpl_imp_released | format_date('d/m/Y') }}</p>";

    $errors = importTemplateRows([($this->row)('tpl-rich', [
        'tpl_imp_released'    => '2026-03-05',
        'tpl_imp_description' => $source,
    ])], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-rich')['common']['tpl_imp_description'])->toBe($source);
})->with('template import formats');

it('imports a rich text template that follows a literal less than sign', function (string $format) {
    $errors = importTemplateRows([($this->row)('tpl-rich-less-than', [
        'tpl_imp_description' => '<p>Size < 10 {{ tpl_imp_name }}</p>',
    ])], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-rich-less-than')['common']['tpl_imp_description'])->toBe('<p>Size &lt; 10 {{ tpl_imp_name }}</p>');
})->with('template import formats');

it('replaces the value of an existing product with the template source', function (string $format) {
    importTemplateRows([($this->row)('tpl-shelf', ['tpl_imp_title' => 'Plain shelf title'])], $format);

    $productId = Product::query()->where('sku', 'tpl-shelf')->sole()->id;

    $errors = importTemplateRows([($this->row)('tpl-shelf', ['tpl_imp_title' => '{{ tpl_imp_name }} shelf'])], $format);

    expect($errors)->toBe([])
        ->and(Product::query()->where('sku', 'tpl-shelf')->sole()->id)->toBe($productId)
        ->and(templateImportValues('tpl-shelf')['common']['tpl_imp_title'])->toBe('{{ tpl_imp_name }} shelf');
})->with('template import formats');

it('stores locale and channel templates in the scope of their row', function (string $format) {
    $localized = fn (string $locale, string $label): array => ($this->row)('tpl-lamp', [
        'tpl_imp_name'     => 'Lamp',
        'tpl_imp_summary'  => $label.' {{ tpl_imp_name | lower }}',
        'tpl_imp_headline' => '{{ tpl_imp_name }} '.$label,
    ], $locale);

    $errors = importTemplateRows([$localized('en_US', 'EN'), $localized('de_DE', 'DE')], $format);

    $values = templateImportValues('tpl-lamp');

    expect($errors)->toBe([])
        ->and($values['locale_specific'])->toEqual([
            'en_US' => ['tpl_imp_summary' => 'EN {{ tpl_imp_name | lower }}'],
            'de_DE' => ['tpl_imp_summary' => 'DE {{ tpl_imp_name | lower }}'],
        ])
        ->and($values['channel_locale_specific'][$this->channel->code])->toEqual([
            'en_US' => ['tpl_imp_headline' => '{{ tpl_imp_name }} EN'],
            'de_DE' => ['tpl_imp_headline' => '{{ tpl_imp_name }} DE'],
        ]);
})->with('template import formats');

it('accepts a template placed before the columns it references', function (string $format) {
    $errors = importTemplateRows([[
        'tpl_imp_title'       => '{{ tpl_imp_name | title }}',
        'tpl_imp_description' => '<p>{{ tpl_imp_brand }}</p>',
        ...($this->row)('tpl-reversed'),
        'tpl_imp_brand'       => 'Halden',
        'tpl_imp_name'        => 'linea desk',
    ]], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-reversed')['common'])->toMatchArray([
            'tpl_imp_title'       => '{{ tpl_imp_name | title }}',
            'tpl_imp_description' => '<p>{{ tpl_imp_brand }}</p>',
        ]);
})->with('template import formats');

it('imports a template whose referenced attribute has no value', function (string $format) {
    $errors = importTemplateRows([($this->row)('tpl-unbranded', ['tpl_imp_title' => '{{ tpl_imp_brand }} desk'])], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-unbranded')['common']['tpl_imp_title'])->toBe('{{ tpl_imp_brand }} desk');
})->with('template import formats');

it('rejects an invalid template on its own row and column and imports the other rows', function (string $format) {
    $errors = importTemplateRows([
        ($this->row)('tpl-valid', ['tpl_imp_title' => '{{ tpl_imp_name }}']),
        ($this->row)('tpl-unknown-attribute', ['tpl_imp_title' => '{{ tpl_imp_missing }}']),
        ($this->row)('tpl-unknown-filter', ['tpl_imp_title' => '{{ tpl_imp_name | shout }}']),
        ($this->row)('tpl-date-without-format', ['tpl_imp_title' => '{{ tpl_imp_released | format_date }}']),
    ], $format);

    expect($errors)->toBe([
        templateImportLine($format, 2) => [templateImportError('tpl_imp_title', 'The attribute "tpl_imp_missing" used in the template does not exist.')],
        templateImportLine($format, 3) => [templateImportError('tpl_imp_title', 'The filter "shout" is not supported. Available filters: capitalize, format_date, lower, slug, title, upper.')],
        templateImportLine($format, 4) => [templateImportError('tpl_imp_title', 'The filter "format_date" in "tpl_imp_released | format_date" has a missing or invalid argument.')],
    ])->and(importedTemplateSkus(['tpl-valid', 'tpl-unknown-attribute', 'tpl-unknown-filter', 'tpl-date-without-format']))->toBe(['tpl-valid']);
})->with('template import formats');

it('reports a percent sign inside an expression without failing the import', function (string $format) {
    $errors = importTemplateRows([
        ($this->row)('tpl-strftime', ['tpl_imp_title' => "{{ tpl_imp_released | format_date('%d/%m/%Y') }}"]),
        ($this->row)('tpl-discount', ['tpl_imp_title' => 'Save {{ 50% off }}']),
        ($this->row)('tpl-percent', ['tpl_imp_title' => '{{ tpl_imp_name }} 100%']),
    ], $format);

    expect($errors)->toBe([
        templateImportLine($format, 1) => [templateImportError('tpl_imp_title', 'The filter "format_date" in "tpl_imp_released | format_date(\'%d/%m/%Y\')" has a missing or invalid argument.')],
        templateImportLine($format, 2) => [templateImportError('tpl_imp_title', 'The template expression "50% off" is invalid. Use an attribute code followed by optional filters, for example: name | upper.')],
    ])->and(importedTemplateSkus(['tpl-strftime', 'tpl-discount', 'tpl-percent']))->toBe(['tpl-percent'])
        ->and(templateImportValues('tpl-percent')['common']['tpl_imp_title'])->toBe('{{ tpl_imp_name }} 100%');
})->with('template import formats');

it('keeps braces as plain text on an attribute without templates', function (string $format) {
    $errors = importTemplateRows([($this->row)('tpl-plain', ['tpl_imp_note' => 'Use {{ anything | shout }}'])], $format);

    expect($errors)->toBe([])
        ->and(templateImportValues('tpl-plain')['common']['tpl_imp_note'])->toBe('Use {{ anything | shout }}');
})->with('template import formats');

it('imports templates on a product model and validates them on its variants', function (string $format) {
    app(VariantStructureWriter::class)->create($this->family->refresh(), [
        'code'       => 'tpl_imp_by_color',
        'name'       => 'tpl_imp_by_color',
        'levels'     => 1,
        'axes'       => ['level_1' => ['tpl_imp_color']],
        'placements' => ['variant' => ['tpl_imp_variant_title']],
    ]);

    $variant = fn (string $color, string $title): array => ($this->row)('tpl-chair-'.$color, [
        'parent'                => 'tpl-chair',
        'tpl_imp_color'         => $color,
        'tpl_imp_variant_title' => $title,
    ]);

    $errors = importTemplateRows([
        ($this->row)('tpl-chair', [
            'type'              => 'configurable',
            'variant_structure' => 'tpl_imp_by_color',
            'tpl_imp_name'      => 'Chair',
            'tpl_imp_title'     => '{{ tpl_imp_name | title }}',
        ]),
        $variant('red', '{{ tpl_imp_name }} in {{ tpl_imp_color }}'),
        $variant('blue', '{{ tpl_imp_missing }}'),
    ], $format);

    $model = Product::query()->where('sku', 'tpl-chair')->sole();
    $red = Product::query()->where('sku', 'tpl-chair-red')->sole();

    expect($errors)->toBe([
        templateImportLine($format, 3) => [templateImportError('tpl_imp_variant_title', 'The attribute "tpl_imp_missing" used in the template does not exist.')],
    ])
        ->and($model->values['common']['tpl_imp_title'])->toBe('{{ tpl_imp_name | title }}')
        ->and($red->parent_id)->toBe($model->id)
        ->and($red->values['common']['tpl_imp_variant_title'])->toBe('{{ tpl_imp_name }} in {{ tpl_imp_color }}')
        ->and(importedTemplateSkus(['tpl-chair-blue']))->toBe([]);
})->with('template import formats');

it('rejects a template that references an attribute outside the product family', function (string $format) {
    $this->variableAttribute('tpl_imp_outsider');

    $errors = importTemplateRows([
        ($this->row)('tpl-family-valid', ['tpl_imp_title' => '{{ tpl_imp_name }}']),
        ($this->row)('tpl-family-outsider', ['tpl_imp_title' => '{{ tpl_imp_name }} {{ tpl_imp_outsider }}']),
    ], $format);

    expect($errors)->toBe([
        templateImportLine($format, 2) => [templateImportError('tpl_imp_title', 'The attribute "tpl_imp_outsider" used in the template does not belong to the product family.')],
    ])->and(importedTemplateSkus(['tpl-family-valid', 'tpl-family-outsider']))->toBe(['tpl-family-valid']);
})->with('template import formats');

it('rejects circular references between the template columns of a row', function (string $format) {
    $cycle = 'Circular template reference detected: tpl_imp_title → tpl_imp_description → tpl_imp_title.';

    $errors = importTemplateRows([
        ($this->row)('tpl-chain', [
            'tpl_imp_title'       => '{{ tpl_imp_description }}',
            'tpl_imp_description' => '<p>{{ tpl_imp_name }}</p>',
        ]),
        ($this->row)('tpl-cycle', [
            'tpl_imp_title'       => '{{ tpl_imp_description }}',
            'tpl_imp_description' => '<p>{{ tpl_imp_title }}</p>',
        ]),
    ], $format);

    expect($errors)->toBe([
        templateImportLine($format, 2) => [
            templateImportError('tpl_imp_title', $cycle),
            templateImportError('tpl_imp_description', $cycle),
        ],
    ])->and(importedTemplateSkus(['tpl-chain', 'tpl-cycle']))->toBe(['tpl-chain']);
})->with('template import formats');

it('detects a circular reference between common and locale values of a row', function (string $format) {
    $cycle = 'Circular template reference detected: tpl_imp_title → tpl_imp_summary → tpl_imp_title.';

    $errors = importTemplateRows([($this->row)('tpl-scoped-cycle', [
        'tpl_imp_title'   => '{{ tpl_imp_summary }}',
        'tpl_imp_summary' => '{{ tpl_imp_title | upper }}',
    ])], $format);

    expect($errors)->toBe([
        templateImportLine($format, 1) => [
            templateImportError('tpl_imp_title', $cycle),
            templateImportError('tpl_imp_summary', $cycle),
        ],
    ])->and(importedTemplateSkus(['tpl-scoped-cycle']))->toBe([]);
})->with('template import formats');

it('reports a self reference once without a circular reference error', function (string $format) {
    $errors = importTemplateRows([($this->row)('tpl-self', [
        'tpl_imp_title'       => '{{ tpl_imp_title }}',
        'tpl_imp_description' => '<p>{{ tpl_imp_title }}</p>',
    ])], $format);

    expect($errors)->toBe([
        templateImportLine($format, 1) => [templateImportError('tpl_imp_title', 'The template cannot reference its own attribute "tpl_imp_title".')],
    ]);
})->with('template import formats');
