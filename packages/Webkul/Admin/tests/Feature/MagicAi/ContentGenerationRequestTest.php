<?php

use Illuminate\Support\Facades\Validator;
use Webkul\Admin\Http\Requests\MagicAI\ContentGenerationRequest;

/*
 * The content generation request must bound the AI overrides so an oversized
 * max_tokens (cost/DoS), an out-of-range temperature, or an unbounded custom
 * system prompt cannot be forwarded to the provider.
 */
function contentRules(): array
{
    return (new ContentGenerationRequest)->rules();
}

it('rejects an oversized max_tokens', function () {
    $validator = Validator::make(['model' => 'gpt-4', 'prompt' => 'x', 'max_tokens' => 999999], contentRules());

    expect($validator->errors()->has('max_tokens'))->toBeTrue();
});

it('rejects an out-of-range temperature', function () {
    $validator = Validator::make(['model' => 'gpt-4', 'prompt' => 'x', 'temperature' => 9], contentRules());

    expect($validator->errors()->has('temperature'))->toBeTrue();
});

it('accepts bounded overrides', function () {
    $validator = Validator::make(
        ['model' => 'gpt-4', 'prompt' => 'x', 'max_tokens' => 1054, 'temperature' => 0.7, 'system_prompt_text' => 'Be concise.'],
        contentRules()
    );

    expect($validator->fails())->toBeFalse();
});
