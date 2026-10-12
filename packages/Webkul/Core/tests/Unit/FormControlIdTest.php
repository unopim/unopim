<?php

it('turns an array style field name into a usable dom id', function () {
    expect(form_control_id('additional_data[locale_specific][en_US][name]'))
        ->toBe('additional_data_locale_specific_en_US_name');
});

it('keeps names that are already valid id tokens untouched', function () {
    expect(form_control_id('is_required'))->toBe('is_required');
});

it('appends a suffix so options sharing a field name stay unique', function () {
    expect(form_control_id('color', 'red'))->toBe('color_red');
});

it('returns an empty id for an empty field name', function () {
    expect(form_control_id(null))->toBe('')
        ->and(form_control_id('', 'red'))->toBe('red');
});

it('suffixes repeated ids within the same request', function () {
    expect(unique_form_control_id('code'))->toBe('code')
        ->and(unique_form_control_id('code'))->toBe('code_2')
        ->and(unique_form_control_id('code'))->toBe('code_3')
        ->and(unique_form_control_id('type'))->toBe('type');
});

it('reserves an id without renaming it when suffixing is not allowed', function () {
    expect(unique_form_control_id('sku', allowSuffix: false))->toBe('sku')
        ->and(unique_form_control_id('sku'))->toBe('sku_2');
});

it('never generates an id from an empty value', function () {
    expect(unique_form_control_id(''))->toBe('');
});
