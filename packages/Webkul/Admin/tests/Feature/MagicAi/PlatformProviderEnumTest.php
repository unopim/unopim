<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Admin\Http\Requests\MagicAI\PlatformRequest;

/*
 * The platform provider must be constrained to the AiProvider enum so an unknown
 * provider cannot be stored and then fatally error at generation time.
 */
function platformRules(): array
{
    return (new PlatformRequest)->rules();
}

it('rejects an unknown provider', function () {
    $validator = Validator::make(
        ['label' => 'X', 'provider' => 'not_a_provider', 'models' => 'gpt-4'],
        platformRules()
    );

    expect($validator->errors()->has('provider'))->toBeTrue();
});

it('accepts a known provider', function () {
    $validator = Validator::make(
        ['label' => 'X', 'provider' => 'openai', 'models' => 'gpt-4'],
        platformRules()
    );

    expect($validator->errors()->has('provider'))->toBeFalse();
});
