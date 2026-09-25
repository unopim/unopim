<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ChatUploadStore;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\Core\Models\CoreConfig;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

beforeEach(function () {
    Storage::fake('public');

    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.magic_ai.agentic_pim.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );

    MagicAIPlatform::query()->delete();

    $this->platform = MagicAIPlatform::create([
        'label'      => 'Text Only',
        'provider'   => 'openai',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-oss-20b',
        'status'     => true,
        'is_default' => true,
    ]);

    $this->actingAs(Admin::factory()->create(), 'admin');
});

function fakeChatRunner(): object
{
    $runner = new class(app(ToolRegistry::class)) extends AgentRunner
    {
        /** @var array<int, ChatContext> */
        public array $contexts = [];

        public function run(ChatContext $context): array
        {
            $this->contexts[] = $context;

            return ['reply' => 'ok', 'action' => 'agent_response', 'data' => []];
        }
    };

    app()->instance(AgentRunner::class, $runner);

    return $runner;
}

function fakeImageRejection(): void
{
    Http::fake(['*' => Http::response([
        'error' => [
            'code'    => 'invalid_prompt',
            'message' => 'gpt-oss-20b does not support the following requested features: input.image.',
        ],
    ], 400)]);
}

function chatPayload(array $overrides = []): array
{
    return array_merge([
        'message'         => 'Create a product from the uploaded image.',
        'platform_id'     => test()->platform->id,
        'model'           => 'gpt-oss-20b',
        'conversation_id' => 'session_1_first',
    ], $overrides);
}

it('does not re-attach an image from an earlier conversation when a new conversation starts', function () {
    $runner = fakeChatRunner();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))->assertOk();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'message'         => 'Under-Desk Cable Tray',
        'conversation_id' => 'session_2_second',
    ]))->assertOk();

    expect($runner->contexts[0]->uploadedImagePaths)->toHaveCount(1)
        ->and($runner->contexts[1]->uploadedImagePaths)->toBe([]);
});

it('keeps the uploaded image for follow-up turns of the same conversation', function () {
    $runner = fakeChatRunner();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))->assertOk();

    $this->post(route('ai-agent.chat.send'), chatPayload(['message' => 'Yes, proceed']))->assertOk();

    expect($runner->contexts[1]->uploadedImagePaths)->toBe($runner->contexts[0]->uploadedImagePaths);
});

it('stops attaching an image once it is older than ten minutes', function () {
    $runner = fakeChatRunner();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))->assertOk();

    $this->travel(11)->minutes();

    $this->post(route('ai-agent.chat.send'), chatPayload(['message' => 'Yes, proceed']))->assertOk();

    expect($runner->contexts[1]->uploadedImagePaths)->toBe([]);
});

it('does not attach another admin\'s upload that shares the conversation id', function () {
    $runner = fakeChatRunner();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))->assertOk();

    $this->actingAs(Admin::factory()->create(), 'admin');

    $this->post(route('ai-agent.chat.send'), chatPayload(['message' => 'Yes, proceed']))->assertOk();

    expect($runner->contexts[1]->uploadedImagePaths)->toBe([]);
});

it('rejects a conversation id that could escape the upload directory', function () {
    fakeChatRunner();

    $this->postJson(route('ai-agent.chat.send'), chatPayload(['conversation_id' => '../../etc']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('conversation_id');
});

it('explains that the model cannot read images and drops the image from the conversation', function () {
    fakeImageRejection();

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))
        ->assertUnprocessable()
        ->assertJsonPath('reply', trans('ai-agent::app.common.error-image-input-unsupported'))
        ->assertJsonPath('discard_images', true);

    expect(app(ChatUploadStore::class)->images([], 'session_1_first'))->toBe([]);
});

it('drops a rejected image during a streamed turn so the next message is not blocked by it', function () {
    fakeImageRejection();

    $paths = app(ChatUploadStore::class)->images([UploadedFile::fake()->image('h.png')], 'session_1_first');

    $context = new ChatContext(
        message: 'Under-Desk Cable Tray',
        history: [],
        productId: null,
        productSku: null,
        productName: null,
        locale: 'en_US',
        channel: 'default',
        platform: $this->platform,
        model: 'gpt-oss-20b',
        uploadedImagePaths: $paths,
    );

    $runner = new class(app(ToolRegistry::class)) extends AgentRunner
    {
        protected function disableOutputBuffering(): void {}
    };

    ob_start();
    $runner->runStreaming($context)->sendContent();
    $output = (string) ob_get_clean();

    expect($output)->toContain('event: error')
        ->and($output)->toContain(json_encode(trans('ai-agent::app.common.error-image-input-unsupported')))
        ->and(app(ChatUploadStore::class)->images([], 'session_1_first'))->toBe([]);
});

it('states the server upload limit when PHP rejects an oversize image', function () {
    fakeChatRunner();

    $path = UploadedFile::fake()->image('h.png')->getPathname();
    $oversize = new UploadedFile($path, 'h.png', 'image/png', UPLOAD_ERR_INI_SIZE, true);

    $this->postJson(route('ai-agent.chat.send'), chatPayload(['images' => [$oversize]]))
        ->assertUnprocessable()
        ->assertJsonPath('errors', [
            'images.0' => [trans('ai-agent::app.common.upload-exceeds-server-limit', [
                'size' => Number::fileSize(UploadedFile::getMaxFilesize()),
            ])],
        ]);
});

it('tells the widget to keep attachments for retry when the failure is not an image refusal', function () {
    Http::fake(['*' => Http::response(['error' => ['message' => 'Invalid API key provided.']], 401)]);

    $this->post(route('ai-agent.chat.send'), chatPayload([
        'images' => [UploadedFile::fake()->image('h.png')],
    ]))->assertJsonPath('discard_images', false);
});
