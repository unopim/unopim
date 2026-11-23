<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Attribute\Importer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Tests\Concerns\InteractsWithTemplates;

uses(InteractsWithTemplates::class);

beforeEach(function () {
    Storage::fake('private');
});

function importTemplateAttributeRows(array $rows): array
{
    $columns = array_keys(array_merge(...$rows));

    $handle = fopen('php://temp', 'r+');

    fputcsv($handle, $columns, escape: '');

    foreach ($rows as $row) {
        fputcsv($handle, array_map(fn (string $column): string => (string) ($row[$column] ?? ''), $columns), escape: '');
    }

    rewind($handle);

    $settings = [
        'type'                  => 'import',
        'action'                => Import::ACTION_APPEND,
        'field_separator'       => ',',
        'file_path'             => 'import/attribute-templates-'.Str::uuid().'.csv',
        'images_directory_path' => '',
    ];

    Storage::disk('private')->put($settings['file_path'], (string) stream_get_contents($handle));

    $jobTrack = JobTrack::factory()->create([
        ...$settings,
        'job_instances_id'    => JobInstances::factory()->create([...$settings, 'code' => 'attribute-templates-'.Str::uuid(), 'entity_type' => 'attributes'])->id,
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

function importedTemplatesFlag(string $code): ?int
{
    $flag = Attribute::query()->where('code', $code)->value('enable_templates');

    return $flag === null ? null : (int) $flag;
}

function unsupportedTemplatesError(): array
{
    return [
        'code'    => Importer::ERROR_TEMPLATES_UNSUPPORTED,
        'column'  => 'enable_templates',
        'message' => trans('data_transfer::app.importers.attributes.validation.errors.templates-unsupported'),
    ];
}

it('imports the templates flag for new and existing attributes', function () {
    $this->variableAttribute('tpl_attr_body', 'textarea');
    $this->templateAttribute('tpl_attr_summary');

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_body', 'type' => 'textarea', 'locale' => 'en_US', 'name' => 'Body', 'enable_templates' => '1', 'productCounts' => '0'],
        ['code' => 'tpl_attr_summary', 'type' => 'textarea', 'locale' => 'en_US', 'name' => 'Summary', 'enable_templates' => '0', 'productCounts' => '0'],
        ['code' => 'tpl_attr_intro', 'type' => 'textarea', 'locale' => 'en_US', 'name' => 'Intro', 'enable_templates' => '1', 'productCounts' => '0'],
    ]);

    expect($errors)->toBe([])
        ->and(importedTemplatesFlag('tpl_attr_body'))->toBe(1)
        ->and(importedTemplatesFlag('tpl_attr_summary'))->toBe(0)
        ->and(importedTemplatesFlag('tpl_attr_intro'))->toBe(1);
});

it('keeps the current flag when the file does not set it', function (array $flag) {
    $this->templateAttribute('tpl_attr_summary');
    $this->variableAttribute('tpl_attr_body', 'textarea');

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_summary', 'locale' => 'en_US', 'name' => 'Renamed summary', ...$flag],
        ['code' => 'tpl_attr_body', 'locale' => 'en_US', 'name' => 'Renamed body', ...$flag],
    ]);

    expect($errors)->toBe([])
        ->and(importedTemplatesFlag('tpl_attr_summary'))->toBe(1)
        ->and(importedTemplatesFlag('tpl_attr_body'))->toBe(0)
        ->and(Attribute::query()->where('code', 'tpl_attr_summary')->sole()->translate('en_US')->name)->toBe('Renamed summary');
})->with([
    'column missing' => [[]],
    'empty cell'     => [['enable_templates' => '']],
]);

it('rejects enabling templates on attributes that cannot hold them', function () {
    $this->variableAttribute('tpl_attr_color', 'select');
    $this->variableAttribute('tpl_attr_email', 'text', ['validation' => 'email']);

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_color', 'type' => 'select', 'locale' => 'en_US', 'enable_templates' => '1', 'is_unique' => ''],
        ['code' => 'tpl_attr_email', 'type' => 'text', 'locale' => 'en_US', 'enable_templates' => '1', 'is_unique' => ''],
        ['code' => 'tpl_attr_serial', 'type' => 'text', 'locale' => 'en_US', 'enable_templates' => '1', 'is_unique' => '1'],
        ['code' => 'tpl_attr_intro', 'type' => 'textarea', 'locale' => 'en_US', 'enable_templates' => '1', 'is_unique' => ''],
    ]);

    expect($errors)->toBe([
        1 => [unsupportedTemplatesError()],
        2 => [unsupportedTemplatesError()],
        3 => [unsupportedTemplatesError()],
    ])->and(importedTemplatesFlag('tpl_attr_color'))->toBe(0)
        ->and(importedTemplatesFlag('tpl_attr_email'))->toBe(0)
        ->and(importedTemplatesFlag('tpl_attr_serial'))->toBeNull()
        ->and(importedTemplatesFlag('tpl_attr_intro'))->toBe(1);
});

it('rejects a settings change that would break an existing template attribute', function () {
    $this->variableAttribute('tpl_attr_title', 'text', ['enable_templates' => 1]);
    $this->variableAttribute('tpl_attr_subtitle', 'text', ['enable_templates' => 1]);
    $this->variableAttribute('tpl_attr_tagline', 'text', ['enable_templates' => 1]);

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_title', 'locale' => 'en_US', 'validation' => 'email', 'is_unique' => '', 'enable_templates' => ''],
        ['code' => 'tpl_attr_subtitle', 'locale' => 'en_US', 'validation' => '', 'is_unique' => '1', 'enable_templates' => ''],
        ['code' => 'tpl_attr_tagline', 'locale' => 'en_US', 'validation' => 'email', 'is_unique' => '', 'enable_templates' => '0'],
    ]);

    $title = Attribute::query()->where('code', 'tpl_attr_title')->sole();
    $subtitle = Attribute::query()->where('code', 'tpl_attr_subtitle')->sole();
    $tagline = Attribute::query()->where('code', 'tpl_attr_tagline')->sole();

    expect($errors)->toBe([
        1 => [unsupportedTemplatesError()],
        2 => [unsupportedTemplatesError()],
    ])->and($title->validation)->toBeNull()
        ->and($title->canHoldTemplate())->toBeTrue()
        ->and((bool) $subtitle->is_unique)->toBeFalse()
        ->and($subtitle->canHoldTemplate())->toBeTrue()
        ->and($tagline->validation)->toBe('email')
        ->and(importedTemplatesFlag('tpl_attr_tagline'))->toBe(0);
});

it('reads every locale row of a new attribute as one attribute', function () {
    Locale::query()->updateOrCreate(['code' => 'de_DE'], ['status' => 1]);

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_story', 'type' => 'textarea', 'locale' => 'en_US', 'name' => 'Story', 'enable_templates' => '1'],
        ['code' => 'tpl_attr_story', 'type' => '', 'locale' => 'de_DE', 'name' => 'Geschichte', 'enable_templates' => '1'],
    ]);

    $story = Attribute::query()->where('code', 'tpl_attr_story')->sole();

    expect($errors)->toBe([])
        ->and($story->type)->toBe('textarea')
        ->and($story->canHoldTemplate())->toBeTrue()
        ->and($story->translate('de_DE')->name)->toBe('Geschichte');
});

it('skips a row whose templates flag is not a boolean', function () {
    $this->variableAttribute('tpl_attr_body', 'textarea');

    $errors = importTemplateAttributeRows([
        ['code' => 'tpl_attr_body', 'locale' => 'en_US', 'enable_templates' => 'maybe'],
    ]);

    expect($errors[1][0])->toMatchArray(['code' => 'invalid_boolean', 'column' => 'enable_templates'])
        ->and($errors[1][0]['message'])->toContain('maybe')
        ->and(importedTemplatesFlag('tpl_attr_body'))->toBe(0);
});
