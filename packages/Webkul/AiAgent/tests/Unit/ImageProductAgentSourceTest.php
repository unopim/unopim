<?php

use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\Contracts\AgentServiceContract;

function imageAgentPrepare(string $source): string
{
    $agent = new ImageProductAgent(Mockery::mock(AgentServiceContract::class));

    return (fn (string $source): string => $this->prepareImageContent($source))->call($agent, $source);
}

it('passes a base64 image through instead of treating it as a file path', function () {
    $base64 = base64_encode('png-bytes');

    expect(imageAgentPrepare($base64))->toBe($base64);
});

it('passes a jpeg base64 image starting with a slash through', function () {
    $base64 = '/9j/4AAQSkZJRg==';

    expect(imageAgentPrepare($base64))->toBe($base64);
});

it('still rejects a missing absolute file path', function () {
    imageAgentPrepare('/tmp/does-not-exist-'.uniqid().'.jpg');
})->throws(InvalidArgumentException::class, 'Image file not found');
