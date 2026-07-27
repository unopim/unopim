<?php

use Illuminate\Support\Facades\Validator;
use Webkul\AiAgent\Http\Requests\CredentialForm;

/*
 * Guards against SSRF through the AI credential apiUrl: the server fetches this
 * URL with the provider bearer token, so private / link-local / metadata
 * addresses must be rejected at validation.
 */
function credentialPayload(string $apiUrl): array
{
    return [
        'label'    => 'Test',
        'provider' => 'custom',
        'apiUrl'   => $apiUrl,
        'apiKey'   => 'sk-test',
        'model'    => 'gpt-4',
    ];
}

it('rejects an api url pointing at the cloud metadata address', function () {
    $validator = Validator::make(
        credentialPayload('http://169.254.169.254/latest/meta-data'),
        (new CredentialForm)->rules()
    );

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('apiUrl'))->toBeTrue();
});

it('rejects an api url pointing at a private address', function () {
    $validator = Validator::make(
        credentialPayload('http://10.0.0.5:8080/v1'),
        (new CredentialForm)->rules()
    );

    expect($validator->errors()->has('apiUrl'))->toBeTrue();
});

it('accepts an api url on a public address', function () {
    $validator = Validator::make(
        credentialPayload('https://8.8.8.8/v1'),
        (new CredentialForm)->rules()
    );

    expect($validator->errors()->has('apiUrl'))->toBeFalse();
});
