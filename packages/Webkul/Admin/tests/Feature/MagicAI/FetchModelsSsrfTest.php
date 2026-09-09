<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/*
 * Reproduction of the MagicAI fetch-models SSRF gap.
 *
 * MagicAIPlatformController::fetchModels validates the SUBMITTED api_url string
 * with SafeWebhookUrl, then AiProvider::fetchModels fetches it with a bare
 * `new Client(['timeout' => 15])`. That client follows redirects and re-resolves
 * DNS, so a public URL that passes validate() can 30x-redirect the server-side
 * fetch to an internal host (cloud metadata / RFC1918). These tests reproduce
 * the primitive without network I/O by driving the exact client shape the code
 * builds through a mocked redirect chain.
 */
it('reproduces: the submitted url passes validation yet the client follows a redirect to an internal host', function () {
    // A public host passes the pre-fetch safety check; a literal public IP is
    // used so the check is deterministic offline (no DNS in the test env).
    expect(SafeWebhookUrl::validate('https://1.1.1.1/models')['valid'])->toBeTrue();

    $reached = [];

    // Exact client configuration used by AiProvider::fetchModels().
    $mock = new MockHandler([
        new Response(302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
        new Response(200, [], json_encode(['data' => [['id' => 'internal-secret']]])),
    ]);
    $stack = HandlerStack::create($mock);
    $stack->push(function (callable $handler) use (&$reached) {
        return function ($request, array $options) use ($handler, &$reached) {
            $reached[] = (string) $request->getUri();

            return $handler($request, $options);
        };
    });

    $client = new Client(['timeout' => 15, 'handler' => $stack]);
    $client->get('https://1.1.1.1/models');

    expect($reached)->toContain('http://169.254.169.254/latest/meta-data/');
});

it('confirms the fix primitive: SafeWebhookUrl::httpOptions disables redirect following', function () {
    $mock = new MockHandler([
        new Response(302, ['Location' => 'http://169.254.169.254/latest/meta-data/']),
    ]);
    $client = new Client(['timeout' => 15, 'handler' => HandlerStack::create($mock)]);

    $response = $client->get(
        'https://1.1.1.1/models',
        SafeWebhookUrl::httpOptions('https://1.1.1.1/models')
    );

    // With allow_redirects=false the 302 is returned, never followed inward.
    expect($response->getStatusCode())->toBe(302);
});
