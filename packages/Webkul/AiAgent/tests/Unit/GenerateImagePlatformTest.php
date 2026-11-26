<?php

use Illuminate\Support\Facades\DB;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ImageModelSelector;
use Webkul\AiAgent\Chat\Tools\EditImage;
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

it('prefers the configured image model when the platform exposes it', function (): void {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_model', 'gpt-image-2.6-sunburst');

    $platform = imagePlatform(['id' => 405, 'provider' => 'openai', 'models' => 'gpt-image-2.6-flare,gpt-image-2.6-sunburst']);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform), $platform))->toBe('gpt-image-2.6-sunburst');
});

it('skips a configured image model the provider has retired', function (): void {
    setImageGenerationConfig('general.magic_ai.image_generation.ai_model', 'dall-e-3');

    $platform = imagePlatform(['id' => 405, 'provider' => 'openai', 'models' => 'gpt-5.4,dall-e-3']);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform), $platform))->toBe('gpt-image-2.6-flare');
});

it('scopes the image platform credentials instead of mutating provider config', function () {
    $source = file_get_contents(base_path('packages/Webkul/AiAgent/src/Chat/Tools/GenerateImage.php'));

    expect($source)->toContain('ScopedProviderConfig::run');
    expect($source)->not->toContain('config(["ai.providers.');
    expect($source)->not->toContain('config($originalConfig)');
});

dataset('image model choices', [
    'openai text models only falls back to a current model'         => ['openai', 'gpt-5.4', 'gpt-5.4,gpt-5.4-mini', 'gpt-image-2.6-flare'],
    'openai skips gpt-image-1.5 when a current model is listed'     => ['openai', 'gpt-5.4', 'gpt-5.4,gpt-image-1.5,gpt-image-2.6-flare', 'gpt-image-2.6-flare'],
    'openai skips a list holding only retired image models'         => ['openai', 'gpt-5.4', 'gpt-5.4,gpt-image-1,gpt-image-1-mini,gpt-image-1.5,dall-e-3,dall-e-2', 'gpt-image-2.6-flare'],
    'openai keeps the current image model the platform lists'       => ['openai', 'gpt-5.4', 'gpt-5.4,gpt-image-2.6-sunburst', 'gpt-image-2.6-sunburst'],
    'openai picks an unknown newer image model from the list'       => ['openai', 'gpt-5.4', 'gpt-5.4,gpt-image-3-preview', 'gpt-image-3-preview'],
    'openai uses an image model chosen as the chat model'           => ['openai', 'gpt-image-2.6-sunburst', 'gpt-image-2.6-sunburst,gpt-image-2.6-flare', 'gpt-image-2.6-sunburst'],
    'openai ignores a retired image model chosen as the chat model' => ['openai', 'dall-e-3', 'dall-e-3', 'gpt-image-2.6-flare'],
    'gemini never returns the text chat model'                      => ['gemini', 'gemini-2.5-flash', 'gemini-2.5-flash,gemini-3.1-flash-image', 'gemini-3.1-flash-image'],
    'gemini never returns a text model from the list'               => ['gemini', 'gemini-3-flash', 'gemini-2.5-pro,gemini-3-flash,gemini-3.1-flash-image', 'gemini-3.1-flash-image'],
    'gemini finds nano banana in the list'                          => ['gemini', 'gemini-3-flash', 'gemini-3-flash,gemini-nano-banana-2.1', 'gemini-nano-banana-2.1'],
    'gemini text models only falls back to a current model'         => ['gemini', 'gemini-3-flash', 'gemini-3-flash,gemini-3.1-flash-lite', 'gemini-nano-banana-2.1'],
    'gemini skips retired image models'                             => ['gemini', 'gemini-3-flash', 'gemini-3-flash,gemini-2.0-flash-preview-image-generation,imagen-4.0-generate-001,gemini-3-pro-image-preview', 'gemini-nano-banana-2.1'],
    'xai never returns the text chat model'                         => ['xai', 'grok-4', 'grok-4,grok-imagine-image-2.0', 'grok-imagine-image-2.0'],
    'xai text models only falls back to a current model'            => ['xai', 'grok-4', 'grok-4', 'grok-imagine-image-2.0'],
    'a provider without image support keeps the chat model'         => ['anthropic', 'claude-sonnet-4-5', 'claude-sonnet-4-5', 'claude-sonnet-4-5'],
]);

it('picks a working image model for image generation', function (string $provider, string $chatModel, string $models, string $expected): void {
    $platform = imagePlatform(['provider' => $provider, 'models' => $models]);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform, $chatModel), $platform))->toBe($expected);
})->with('image model choices');

it('picks a working image model for image editing', function (string $provider, string $chatModel, string $models, string $expected): void {
    $platform = imagePlatform(['provider' => $provider, 'models' => $models]);

    expect((new EditImage)->resolveImageModel(imageChatContext($platform, $chatModel)))->toBe($expected);
})->with('image model choices');

it('treats shut down and retiring image models as retired', function (string $model): void {
    expect(ImageModelSelector::isRetired($model))->toBeTrue();
})->with([
    'dall-e-2',
    'dall-e-3',
    'gpt-image-1',
    'gpt-image-1-mini',
    'gpt-image-1.5',
    'gpt-image-1.5-2025-12-16',
    'gemini-2.0-flash-preview-image-generation',
    'gemini-2.0-flash-exp-image-generation',
    'gemini-3-pro-image-preview',
    'imagen-3.0-generate-002',
    'imagen-4.0-generate-001',
]);

it('keeps current image models usable', function (string $provider, string $model): void {
    expect(ImageModelSelector::isRetired($model))->toBeFalse()
        ->and(ImageModelSelector::isUsableImageModel($provider, $model))->toBeTrue();
})->with([
    ['openai', 'gpt-image-2.6-flare'],
    ['openai', 'gpt-image-2.6-sunburst'],
    ['openai', 'gpt-image-2.5-flare'],
    ['openai', 'gpt-image-2'],
    ['gemini', 'gemini-nano-banana-2.1'],
    ['gemini', 'gemini-3.1-flash-image'],
    ['gemini', 'gemini-3-pro-image'],
    ['gemini', 'gemini-2.5-flash-image'],
    ['xai', 'grok-imagine-image-2.0'],
]);

it('never treats a text model as an image model', function (string $provider, string $model): void {
    expect(ImageModelSelector::isUsableImageModel($provider, $model))->toBeFalse();
})->with([
    ['openai', 'gpt-5.4'],
    ['gemini', 'gemini-2.5-flash'],
    ['gemini', 'gemini-2.5-pro'],
    ['gemini', 'gemini-3-flash'],
    ['xai', 'grok-4'],
    ['xai', 'grok-imagine-video'],
]);

it('skips a model an install retires in config', function (): void {
    config(['ai-agent.image_models.retired' => [...config('ai-agent.image_models.retired'), '/^gpt-image-2\.6-flare$/']]);

    $platform = imagePlatform(['provider' => 'openai', 'models' => 'gpt-5.4,gpt-image-2.6-flare,gpt-image-2.6-sunburst']);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform, 'gpt-5.4'), $platform))->toBe('gpt-image-2.6-sunburst')
        ->and((new EditImage)->resolveImageModel(imageChatContext($platform, 'gpt-5.4')))->toBe('gpt-image-2.6-sunburst');
});

it('falls back to the image model an install configures for the provider', function (): void {
    config(['ai-agent.image_models.providers.gemini.fallback' => 'gemini-nano-banana-3']);

    $platform = imagePlatform(['provider' => 'gemini', 'models' => 'gemini-3-flash']);

    expect((new GenerateImage)->resolveImageModel(imageChatContext($platform, 'gemini-3-flash'), $platform))->toBe('gemini-nano-banana-3')
        ->and((new EditImage)->resolveImageModel(imageChatContext($platform, 'gemini-3-flash')))->toBe('gemini-nano-banana-3');
});
