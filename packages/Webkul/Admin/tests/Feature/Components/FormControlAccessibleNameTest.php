<?php

use Illuminate\Support\Facades\Blade;

it('names a control after its validation label', function (string $type) {
    expect(Blade::render('<x-admin::form.control-group.control type="'.$type.'" name="code" label="Code" />'))
        ->toContain('aria-label="Code"');
})->with(['text', 'textarea', 'date', 'checkbox', 'switch']);

it('binds the accessible name to a dynamic label', function () {
    expect(Blade::render('<x-admin::form.control-group.control name="code" ::label="fieldLabel" />'))
        ->toContain(':aria-label="fieldLabel"');
});

it('keeps an explicit accessible name and leaves hidden inputs unnamed', function () {
    $named = Blade::render('<x-admin::form.control-group.control name="code" label="Code" aria-label="Explicit" />');
    $hidden = Blade::render('<x-admin::form.control-group.control type="hidden" name="code" label="Code" />');

    expect($named)->toContain('aria-label="Explicit"')
        ->and(substr_count($named, 'aria-label='))->toBe(1)
        ->and($hidden)->not->toContain('aria-label');
});
