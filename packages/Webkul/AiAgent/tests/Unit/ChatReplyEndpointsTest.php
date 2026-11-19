<?php

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Webkul\AiAgent\Jobs\ChatReplyJob;
use Webkul\AiAgent\Models\ChatReply;
use Webkul\AiAgent\Repositories\ChatReplyRepository;
use Webkul\Core\Models\CoreConfig;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    CoreConfig::query()->updateOrCreate(
        ['code' => 'general.magic_ai.agentic_pim.enabled', 'channel_code' => null, 'locale_code' => null],
        ['value' => '1'],
    );

    MagicAIPlatform::query()->delete();

    MagicAIPlatform::create([
        'label'      => 'OpenAI',
        'provider'   => 'openai',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => true,
    ]);

    $this->admin = chatReplyAdmin();
    $this->actingAs($this->admin, 'admin');
});

function chatReplyAdmin(): Admin
{
    return Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);
}

function chatReplyFor(Admin $admin, string $status = ChatReply::STATUS_QUEUED, array $attributes = []): ChatReply
{
    return app(ChatReplyRepository::class)->create(['user_id' => $admin->id, 'status' => $status] + $attributes);
}

it('queues a reply for the conversation and returns its id', function () {
    Queue::fake();

    $response = $this->postJson(route('ai-agent.chat.send-async'), [
        'message'         => 'Find red shoes',
        'conversation_id' => 'session_42',
        'request_id'      => (string) Str::uuid(),
    ])->assertOk()->assertJsonPath('status', ChatReply::STATUS_QUEUED);

    $reply = ChatReply::query()->findOrFail($response->json('reply_id'));

    expect($reply->user_id)->toBe($this->admin->id)
        ->and($reply->conversation_id)->toBe('session_42')
        ->and($reply->status)->toBe(ChatReply::STATUS_QUEUED);

    Queue::assertPushed(ChatReplyJob::class, 1);
});

it('validates the conversation id before storing a reply', function (string $conversationId) {
    Queue::fake();

    $this->postJson(route('ai-agent.chat.send-async'), ['message' => 'Hi', 'conversation_id' => $conversationId])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('conversation_id');

    expect(ChatReply::query()->count())->toBe(0);
    Queue::assertNothingPushed();
})->with([
    'too long'      => [str_repeat('a', 200)],
    'invalid chars' => ['session 42; drop'],
]);

it('returns the progress of an own reply', function () {
    $reply = chatReplyFor($this->admin, ChatReply::STATUS_GENERATING, ['content' => 'Partial', 'agent_status' => 'tool:search_products']);

    $this->getJson(route('ai-agent.chat.reply', ['id' => $reply->id]))
        ->assertOk()
        ->assertJson([
            'status'       => ChatReply::STATUS_GENERATING,
            'content'      => 'Partial',
            'agent_status' => 'tool:search_products',
            'done'         => false,
        ]);
});

it('does not let an admin poll or cancel another admin reply', function () {
    $reply = chatReplyFor(chatReplyAdmin(), ChatReply::STATUS_QUEUED, ['content' => 'Secret']);

    $this->getJson(route('ai-agent.chat.reply', ['id' => $reply->id]))
        ->assertNotFound()
        ->assertJsonMissing(['content' => 'Secret'])
        ->assertJsonPath('error', trans('ai-agent::app.common.reply-not-found'))
        ->assertJsonPath('done', true);

    $this->postJson(route('ai-agent.chat.reply.cancel', ['id' => $reply->id]))
        ->assertOk()
        ->assertJsonPath('cancelled', false);

    expect($reply->refresh()->status)->toBe(ChatReply::STATUS_QUEUED);
});

it('refuses polling and cancelling to an admin without the ai-agent permission', function () {
    $reply = chatReplyFor($this->admin);
    $this->actingAs(Admin::factory()->create([
        'role_id' => Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['dashboard']])->id,
    ]), 'admin');

    $this->getJson(route('ai-agent.chat.reply', ['id' => $reply->id]))->assertForbidden();
    $this->postJson(route('ai-agent.chat.reply.cancel', ['id' => $reply->id]))->assertForbidden();

    expect($reply->refresh()->status)->toBe(ChatReply::STATUS_QUEUED);
});

it('answers 404 for an id that is not a uuid', function () {
    $this->getJson('/'.config('app.admin_url').'/ai-agent/chat/reply/1%20or%201=1')->assertNotFound();
});

it('cancels a reply only while no worker has claimed it', function () {
    $queued = chatReplyFor($this->admin);
    $generating = chatReplyFor($this->admin, ChatReply::STATUS_GENERATING);

    $this->postJson(route('ai-agent.chat.reply.cancel', ['id' => $queued->id]))->assertOk()->assertJsonPath('cancelled', true);
    $this->postJson(route('ai-agent.chat.reply.cancel', ['id' => $generating->id]))->assertOk()->assertJsonPath('cancelled', false);

    expect($queued->refresh()->status)->toBe(ChatReply::STATUS_CANCELLED)
        ->and($queued->isFinished())->toBeTrue()
        ->and($generating->refresh()->status)->toBe(ChatReply::STATUS_GENERATING);
});

it('prunes old reply rows with the cleanup command', function () {
    $old = chatReplyFor($this->admin, ChatReply::STATUS_DONE);
    $old->forceFill(['created_at' => now()->subDays(8)])->save();
    $recent = chatReplyFor($this->admin, ChatReply::STATUS_DONE);

    $this->artisan('ai-agent:cleanup')->assertSuccessful();

    expect(ChatReply::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(ChatReply::query()->whereKey($recent->id)->exists())->toBeTrue();
});
