<?php

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Image;
use Webkul\MagicAI\Agents\MagicContentAgent;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Models\MagicAISystemPrompt;

use function Pest\Laravel\postJson;

function defaultPlatform(AiProvider $provider = AiProvider::OpenAI): MagicAIPlatform
{
    return MagicAIPlatform::factory()->default()->provider($provider)->create([
        'models' => 'gpt-4o-mini,dall-e-3',
    ]);
}

it('denies content generation without the ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    postJson(route('admin.magic_ai.content'), ['model' => 'gpt-4o-mini', 'prompt' => 'Write a description'])
        ->assertForbidden();
});

it('requires a model and a prompt', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.content'), [])->assertJsonValidationErrors(['model', 'prompt']);
});

it('rejects generation options outside their accepted range', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.content'), [
        'model'       => 'gpt-4o-mini',
        'prompt'      => 'Write a description',
        'temperature' => 5,
        'max_tokens'  => 999999,
    ])->assertJsonValidationErrors(['temperature', 'max_tokens']);
});

it('generates content through the default platform', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    MagicContentAgent::fake(['<p>A generated description.</p>']);

    $response = postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ])->assertOk();

    expect($response->json('content'))->toBe('<p>A generated description.</p>')
        ->and($response->json('truncated'))->toBeFalse();
});

it('generates content for every configurable platform provider', function (AiProvider $provider) {
    $this->loginWithPermissions('all');
    defaultPlatform($provider)->update(['api_url' => $provider->defaultUrl() ?: 'https://vendor.test/v1']);

    MagicContentAgent::fake(['<p>Generated.</p>']);

    postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ])->assertOk()->assertJson(['content' => '<p>Generated.</p>']);
})->with(AiProvider::cases());

it('generates content when the caller has no resource to interpolate', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    MagicContentAgent::fake(['<p>ok</p>']);

    postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Write a tagline',
    ])->assertOk()->assertJson(['content' => '<p>ok</p>']);
});

it('rejects a resource reference that is missing its counterpart', function (array $payload) {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.content'), array_merge([
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ], $payload))->assertUnprocessable();
})->with([
    [['resource_id' => 1]],
    [['resource_type' => 'product']],
    [['resource_id' => 1, 'resource_type' => 'invoice']],
]);

it('asks the model in the requested locale', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    MagicContentAgent::fake(['<p>ok</p>']);

    postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ])->assertOk();

    MagicContentAgent::assertPrompted(fn ($prompt): bool => str_contains($prompt->prompt, core()->getRequestedLocaleCode()));
});

it('takes the generation options from the selected system prompt', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    $systemPrompt = MagicAISystemPrompt::factory()->create([
        'tone'        => 'Write like a catalogue copywriter.',
        'temperature' => 0.2,
        'max_tokens'  => 900,
    ]);

    MagicContentAgent::fake(['<p>ok</p>']);

    postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
        'tone'   => $systemPrompt->id,
    ])->assertOk();

    MagicContentAgent::assertPrompted(
        fn ($prompt): bool => $prompt->agent->instructions() === 'Write like a catalogue copywriter.'
    );
});

it('prefers the system prompt text edited in the modal over the stored record', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    $systemPrompt = MagicAISystemPrompt::factory()->create(['tone' => 'Stored tone.']);

    MagicContentAgent::fake(['<p>ok</p>']);

    postJson(route('admin.magic_ai.content'), [
        'model'              => 'gpt-4o-mini',
        'prompt'             => 'Describe this product',
        'tone'               => $systemPrompt->id,
        'system_prompt_text' => 'Edited tone.',
    ])->assertOk();

    MagicContentAgent::assertPrompted(fn ($prompt): bool => $prompt->agent->instructions() === 'Edited tone.');
});

it('falls back to the default token ceiling when the install configures none', function () {
    expect(MagicAI::defaultMaxTokens())->toBe(MagicAI::DEFAULT_MAX_TOKENS);
});

it('clamps a configured token ceiling to the supported maximum', function () {
    DB::table('core_config')->insert([
        'code'       => 'general.magic_ai.settings.max_tokens',
        'value'      => '999999',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(MagicAI::defaultMaxTokens())->toBe(MagicAI::MAX_TOKENS_CEILING);
});

it('reports a readable message when generation fails', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    MagicContentAgent::fake(fn () => throw new RuntimeException('cURL error 28: Operation timed out'));

    $response = postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ])->assertBadRequest();

    expect($response->json('message'))->toBe(trans('admin::app.configuration.platform.message.ai-response-timeout'));
});

it('fails generation when the install has no platform configured', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.content'), [
        'model'  => 'gpt-4o-mini',
        'prompt' => 'Describe this product',
    ])->assertBadRequest();
});

it('denies image generation without the ai-agent permission', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    postJson(route('admin.magic_ai.image'), [
        'prompt' => 'A red shoe',
        'model'  => 'dall-e-3',
        'size'   => '1024x1024',
    ])->assertForbidden();
});

it('validates the image generation options', function () {
    $this->loginWithPermissions('all');

    postJson(route('admin.magic_ai.image'), [
        'prompt'  => 'A red shoe',
        'model'   => 'dall-e-3',
        'size'    => '999x999',
        'quality' => 'ultra',
        'n'       => 50,
    ])->assertJsonValidationErrors(['size', 'quality', 'n']);
});

it('generates an image through the default platform', function () {
    $this->loginWithPermissions('all');
    defaultPlatform();

    Image::fake();

    postJson(route('admin.magic_ai.image'), [
        'prompt' => 'A red shoe on a white background',
        'model'  => 'dall-e-3',
        'size'   => '1024x1024',
    ])->assertOk()->assertJsonStructure(['images']);
});

it('refuses image generation on a platform whose provider has no image endpoint', function () {
    $this->loginWithPermissions('all');
    defaultPlatform(AiProvider::Anthropic);

    postJson(route('admin.magic_ai.image'), [
        'prompt' => 'A red shoe',
        'model'  => 'claude-sonnet-4-5',
        'size'   => '1024x1024',
    ])->assertStatus(500);
});
