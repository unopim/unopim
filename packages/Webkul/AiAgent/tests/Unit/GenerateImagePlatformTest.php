<?php

use Illuminate\Support\Facades\DB;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Tools\GenerateImage;
use Webkul\Core\RequestMemo;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

/**
 * @param  array<string, mixed>  $attributes
 */
function imagePlatform(array $attributes): MagicAIPlatform
{
    $platform = new MagicAIPlatform;

    $platform->forceFill(array_merge([
        'id'      => 1,
        'label'   => 'Platform',
        'status'  => 1,
        'api_url' => null,
    ], $attributes));

    return $platform;
}

function imageChatContext(MagicAIPlatform $platform, string $model = ''): ChatContext
{
    $reflection = new ReflectionClass(ChatContext::class);

    /** @var ChatContext $context */
    $context = $reflection->newInstanceWithoutConstructor();

    $reflection->getProperty('platform')->setValue($context, $platform);
    $reflection->getProperty('model')->setValue($context, $model);

    return $context;
}

function setImageGenerationConfig(string $code, string $value): void
{
    DB::table('core_config')->updateOrInsert(
        ['code' => $code],
        ['value' => $value, 'updated_at' => now(), 'created_at' => now()],
    );

    app(RequestMemo::class)->forget('core_config.');
}

it('falls back to the conversation platform when no image platform is configured', function () {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_platform', '0');

    $chatPlatform = imagePlatform(['id' => 7, 'provider' => 'openai', 'models' => 'gpt-image-1']);

    expect((new GenerateImage)->resolveImagePlatform(imageChatContext($chatPlatform))->id)->toBe(7);
});

it('uses the configured image platform instead of the chat platform', function () {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_platform', '405');

    $chatPlatform = imagePlatform(['id' => 7, 'provider' => 'groq', 'models' => 'gpt-oss-120b']);
    $imagePlatform = imagePlatform(['id' => 405, 'provider' => 'openai', 'models' => 'gpt-image-1']);

    $this->mock(MagicAIPlatformRepository::class, function ($mock) use ($imagePlatform) {
        $mock->shouldReceive('find')->with(405)->andReturn($imagePlatform);
    });

    $resolved = (new GenerateImage)->resolveImagePlatform(imageChatContext($chatPlatform));

    expect($resolved->id)->toBe(405);
    expect($resolved->provider)->toBe('openai');
});

it('ignores a configured image platform that cannot generate images', function () {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_platform', '9');

    $chatPlatform = imagePlatform(['id' => 7, 'provider' => 'openai', 'models' => 'gpt-image-1']);
    $textOnly = imagePlatform(['id' => 9, 'provider' => 'groq', 'models' => 'gpt-oss-120b']);

    $this->mock(MagicAIPlatformRepository::class, function ($mock) use ($textOnly) {
        $mock->shouldReceive('find')->with(9)->andReturn($textOnly);
    });

    expect((new GenerateImage)->resolveImagePlatform(imageChatContext($chatPlatform))->id)->toBe(7);
});

it('ignores a disabled image platform', function () {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_platform', '11');

    $chatPlatform = imagePlatform(['id' => 7, 'provider' => 'openai', 'models' => 'gpt-image-1']);
    $disabled = imagePlatform(['id' => 11, 'provider' => 'openai', 'models' => 'gpt-image-1', 'status' => 0]);

    $this->mock(MagicAIPlatformRepository::class, function ($mock) use ($disabled) {
        $mock->shouldReceive('find')->with(11)->andReturn($disabled);
    });

    expect((new GenerateImage)->resolveImagePlatform(imageChatContext($chatPlatform))->id)->toBe(7);
});

it('prefers the configured image model when the platform exposes it', function () {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_model', 'dall-e-3');

    $platform = imagePlatform(['id' => 405, 'provider' => 'openai', 'models' => 'dall-e-3,gpt-image-1']);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform), $platform))->toBe('dall-e-3');
});
