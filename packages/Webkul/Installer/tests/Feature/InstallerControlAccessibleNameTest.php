<?php

use Illuminate\Support\Facades\Blade;

it('names a text control after its label', function () {
    expect(Blade::render('<x-installer::form.control-group.control name="db_name" label="Database Name" />'))
        ->toContain('aria-label="Database Name"');
});

it('names a select and a checkbox after their label', function (string $type) {
    expect(Blade::render('<x-installer::form.control-group.control type="'.$type.'" name="field" label="Field Label" />'))
        ->toContain('aria-label="Field Label"');
})->with(['select', 'checkbox']);

it('binds the accessible name to a dynamic label', function () {
    expect(Blade::render('<x-installer::form.control-group.control name="field" ::label="fieldLabel" />'))
        ->toContain(':aria-label="fieldLabel"');
});

it('keeps an explicit accessible name and leaves hidden inputs unnamed', function () {
    $named = Blade::render('<x-installer::form.control-group.control name="field" label="Label" aria-label="Explicit" />');
    $hidden = Blade::render('<x-installer::form.control-group.control type="hidden" name="field" label="Label" />');

    expect($named)->toContain('aria-label="Explicit"')
        ->and(substr_count($named, 'aria-label='))->toBe(1)
        ->and($hidden)->not->toContain('aria-label');
});
