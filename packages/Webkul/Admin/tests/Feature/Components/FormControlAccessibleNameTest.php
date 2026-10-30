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

it('names a control after the visible label of its group', function (string $type) {
    $html = Blade::render('<x-admin::form.control-group><x-admin::form.control-group.label>Code</x-admin::form.control-group.label><x-admin::form.control-group.control type="'.$type.'" name="code" /></x-admin::form.control-group>');

    preg_match('/<label[^>]*\bid="([^"]+)"/', $html, $label);

    expect($label)->toHaveKey(1)
        ->and($html)->toContain('aria-labelledby="'.$label[1].'"');
})->with(['text', 'textarea', 'date', 'checkbox', 'switch']);

it('prefers the validation label and keeps an explicitly linked label untouched', function () {
    $validation = Blade::render('<x-admin::form.control-group><x-admin::form.control-group.label>Code</x-admin::form.control-group.label><x-admin::form.control-group.control name="code" label="Code" /></x-admin::form.control-group>');
    $linked = Blade::render('<x-admin::form.control-group><x-admin::form.control-group.label for="code">Code</x-admin::form.control-group.label><x-admin::form.control-group.control name="code" /></x-admin::form.control-group>');

    expect($validation)->toContain('aria-label="Code"')->not->toContain('aria-labelledby')
        ->and($linked)->toContain('for="code"')->not->toContain('aria-labelledby');
});

it('does not carry a label over to a control in another group', function () {
    $html = Blade::render('<x-admin::form.control-group><x-admin::form.control-group.label>Code</x-admin::form.control-group.label></x-admin::form.control-group><x-admin::form.control-group><x-admin::form.control-group.control name="name" /></x-admin::form.control-group>');

    expect($html)->not->toContain('aria-labelledby');
});

it('leaves the label for a vue picker that owns its focusable element', function () {
    $html = Blade::render('<x-admin::form.control-group><x-admin::form.control-group.label>Code</x-admin::form.control-group.label><x-admin::form.control-group.control type="select" name="code" :options="json_encode([])" /><x-admin::form.control-group.control name="name" /></x-admin::form.control-group>');

    expect(substr_count($html, 'aria-labelledby='))->toBe(1);
});
