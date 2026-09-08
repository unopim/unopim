<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Webkul\Webhook\Validators\SafeWebhookUrl;

/*
 * MagicAIPlatformController::fetchModels validates the submitted api_url with
 * SafeWebhookUrl, then AiProvider::fetchModels fetches it. A bare Guzzle client
 * follows redirects and re-resolves DNS, so a public URL that passes validation
 * can 30x-redirect the server-side fetch to an internal host. These tests drive
 * the exact client shape through a mocked redirect chain, without network I/O.
 */
it('reproduces: the submitted url passes validation yet the client follows a redirect to an internal host', function () {
    // A public host passes the pre-fetch safety check; a literal public IP is
    // used so the check is deterministic offline (no DNS in the test env).
    expect(SafeWebhookUrl::validate('https://1.1.1.1/models')['valid'])->toBeTrue();

    $reached = [];

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

    expect($response->getStatusCode())->toBe(302);
});
