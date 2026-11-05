<?php

namespace Webkul\MagicAI\Gateways;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Laravel\Ai\Contracts\Files\StorableFile;
use Laravel\Ai\Contracts\Providers\ImageProvider;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Gateway\OpenAi\OpenAiGateway;

class OpenAiImageGateway extends OpenAiGateway
{
    private const string RETURNS_BASE64_BY_DEFAULT = '/(^|[-_\/])gpt-image|chatgpt-image/i';

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
            ...$this->outputFormatOptions($model),
        ]);
    }

    /**
     * @param  array<int, mixed>  $attachments
     * @param  array<string, mixed>  $providerOptions
     */
    protected function sendImageEditRequest(
        ImageProvider $provider,
        string $model,
        string $prompt,
        array $attachments,
        ?string $size,
        ?string $quality,
        ?int $timeout,
        array $providerOptions = [],
    ) {
        $request = $this->client($provider, $timeout ?? 120);

        $field = self::returnsBase64ByDefault($model) ? 'image[]' : 'image';

        foreach ($attachments as $attachment) {
            $request = $request->attach($field, $this->attachmentContent($attachment), 'image.png');
        }

        return $request->post('images/edits', array_merge($providerOptions, array_filter([
            'model'  => $model,
            'prompt' => $prompt,
            ...$provider->defaultImageOptions($size, $quality),
            ...$this->outputFormatOptions($model),
        ])));
    }

    /**
     * GPT image models reject response_format and always return base64; older models need it requested.
     *
     * @return array<string, string>
     */
    private function outputFormatOptions(string $model): array
    {
        return self::returnsBase64ByDefault($model)
            ? ['moderation' => 'low']
            : ['response_format' => 'b64_json'];
    }

    private function attachmentContent(mixed $attachment): string
    {
        return match (true) {
            $attachment instanceof Image && $attachment instanceof StorableFile  => $attachment->content(),
            $attachment instanceof UploadedFile                                  => (string) $attachment->get(),
            default                                                              => throw new InvalidArgumentException('Unsupported image attachment type ['.get_debug_type($attachment).']'),
        };
    }
}
