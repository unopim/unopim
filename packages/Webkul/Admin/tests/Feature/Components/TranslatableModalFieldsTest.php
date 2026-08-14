<?php

use Illuminate\Support\Str;

/**
 * Label inputs behind the locale switcher rather than one control per locale.
 * An installation carrying a few hundred locales turned these modals into a
 * wall of inputs with no way to reach the far end.
 */
function blade(string $path): string
{
    return file_get_contents(base_path($path));
}

function associationFieldBuilder(): string
{
    return blade('packages/Webkul/Admin/src/Resources/views/components/associations/field-builder.blade.php');
}

function measurementFamilyEdit(): string
{
    return blade('packages/Webkul/Measurement/src/Resources/views/measurement-families/edit.blade.php');
}

it('puts the association field modal label behind the switcher', function () {
    $modal = Str::between(associationFieldBuilder(), '<!-- Locales Inputs -->', '<!-- Input Validation -->');

    expect($modal)->toContain('<x-admin::form.translatable-fields')
        ->and($modal)->toContain("v-show=\"locale === '")
        ->and($modal)->not->toContain('v-for="locale in locales"');
});

it('puts the measurement unit modal label behind the switcher', function () {
    $source = measurementFamilyEdit();

    $modal = Str::between($source, 'v-model="locale.code"', 'conversion');

    expect($modal)->toContain('<x-admin::form.translatable-fields')
        ->and($modal)->toContain("v-show=\"locale === '");
});

it('renames the unit it edits so the slot locale cannot shadow it', function () {
    $source = measurementFamilyEdit();

    expect($source)->toContain("v-model=\"unit.labels['")
        ->and($source)->toContain('unit: {')
        ->and($source)->not->toContain("v-model=\"locale.labels['")
        ->and($source)->not->toContain('as="selectedLocale"');
});

it('gives the unit label a row of its own beside a two column head', function () {
    $source = measurementFamilyEdit();

    expect($source)->toContain('grid grid-cols-2 gap-4')
        ->and($source)->not->toContain('grid grid-cols-3 gap-4');
});

it('leaves the shared translatable component untouched', function () {
    $component = blade('packages/Webkul/Admin/src/Resources/views/components/form/translatable-fields.blade.php');

    expect($component)->toContain('v-slot:default="{ locale }"')
        ->and($component)->not->toContain("'as'");
});
