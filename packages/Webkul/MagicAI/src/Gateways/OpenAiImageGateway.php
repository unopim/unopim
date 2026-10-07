<?php

namespace Webkul\MagicAI\Gateways;

use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Gateway\OpenAi\OpenAiGateway;

class OpenAiImageGateway extends OpenAiGateway
{
    private const RETURNS_BASE64_BY_DEFAULT = '/(^|[-_])gpt-image|chatgpt-image/i';

    public static function returnsBase64ByDefault(string $model): bool
    {
        return (bool) preg_match(self::RETURNS_BASE64_BY_DEFAULT, $model);
    }

    /**
     * @param  array<string, mixed>  $providerOptions
     */
    protected function sendImageGenerationRequest(
        ImageProvider $provider,
        string $model,
        string $prompt,
        ?string $size,
        ?string $quality,
        ?int $timeout,
        array $providerOptions = [],
    ) {
        return $this->client($provider, $timeout ?? 120)->post('images/generations', [
            ...$providerOptions,
            'model'  => $model,
            'prompt' => $prompt,
            ...$provider->defaultImageOptions($size, $quality),
            ...(self::returnsBase64ByDefault($model)
                ? ['moderation' => 'low']
                : ['response_format' => 'b64_json']),
        ]);
    }
}
