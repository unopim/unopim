<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

it('rejects an invalid dpp_gtin when a product is saved', function (): void {
    request()->merge(['values' => ['common' => ['dpp_gtin' => '4006381333932']]]);

    try {
        Event::dispatch('catalog.product.update.before', 1);

        $this->fail('Expected the invalid GTIN to fail validation.');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('values.common.dpp_gtin')
            ->and($e->errors()['values.common.dpp_gtin'][0])->toBe(
                trans('passport::app.validation.gtin', ['attribute' => trans('passport::app.attributes.dpp_gtin')])
            );
    }
});

it('accepts a valid dpp_gtin when a product is saved', function (): void {
    request()->merge(['values' => ['common' => ['dpp_gtin' => '4006381333931']]]);

    Event::dispatch('catalog.product.update.before', 1);
})->throwsNoExceptions();

it('leaves a product without a dpp_gtin unaffected', function (): void {
    request()->merge(['values' => ['common' => ['sku' => 'plain-sku']]]);

    Event::dispatch('catalog.product.update.before', 1);
})->throwsNoExceptions();
