<?php

use Illuminate\Support\Str;

function selectControlSource(): string
{
    return file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/components/form/control-group/control.blade.php')
    );
}

function multiselectSource(): string
{
    return file_get_contents(base_path('node_modules/vue-multiselect/dist/vue-multiselect.esm.js'));
}

it('never pins a select dropdown to open downwards', function () {
    expect(selectControlSource())->not->toContain('open-direction');
});

it('leaves the direction unset on both searchable handlers', function (string $template) {
    $markup = Str::between(selectControlSource(), $template, '</v-multiselect>');

    expect($markup)->toContain(':options="formattedOptions"')
        ->and($markup)->not->toContain('open-direction')
        ->and($markup)->not->toContain('openDirection');
})->with([
    '<script type="text/x-template" id="v-select-handler-template">',
    '<script type="text/x-template" id="v-async-select-handler-template">',
]);

it('flips upwards only while the direction is left unset', function () {
    $adjust = Str::between(multiselectSource(), 'adjustPosition () {', 'filterOptions (');

    expect($adjust)
        ->toContain("hasEnoughSpaceBelow || spaceBelow > spaceAbove || this.openDirection === 'below' || this.openDirection === 'bottom'")
        ->toContain("this.preferredOpenDirection = 'above';");
});

it('keeps the default direction empty so the space check decides', function () {
    $prop = Str::between(multiselectSource(), 'openDirection: {', '},');

    expect($prop)->toContain("default: ''");
});

it('ships the account timezone field as a searchable select, which is the field that sits last', function () {
    $edit = file_get_contents(
        base_path('packages/Webkul/Admin/src/Resources/views/settings/users/edit.blade.php')
    );

    $control = Str::between($edit, 'name="timezone"', '/>');

    expect($control)->toContain('core()->getTimeZones()')
        ->and($control)->not->toContain('open-direction');
});
