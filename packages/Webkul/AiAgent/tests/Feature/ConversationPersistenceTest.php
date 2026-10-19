<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    Http::preventStrayRequests();
    $this->owner = Admin::factory()->create(['role_id' => Role::factory()->create(['permission_type' => 'all'])->id]);
    $this->actingAs($this->owner, 'admin');
});

function historyMessage(string $id, string $content = 'Saved reply'): array
{
    return ['message_id' => $id, 'role' => 'assistant', 'content' => $content];
}

it('disables every conversation endpoint by default without changing stored history', function () {
    app()->setLocale('fr_FR');
    expect(config('ai-agent.history.enabled'))->toBeFalse();
    $count = DB::table('ai_agent_conversations')->count();
    $this->getJson(route('ai-agent.conversations.index'))->assertForbidden()
        ->assertJsonPath('message', trans('ai-agent::app.common.unauthorized'));
    $this->getJson(route('ai-agent.conversations.show', 1))->assertForbidden();
    $this->postJson(route('ai-agent.conversations.store'), ['messages' => [historyMessage('reply')]])->assertForbidden();
    expect(DB::table('ai_agent_conversations')->count())->toBe($count);
});

it('persists structured results after an extension opts in and scopes access to their owner', function () {
    config(['ai-agent.history.enabled' => true]);
    $message = historyMessage('reply') + ['presentation' => ['type' => 'example.lookup', 'id' => 'result-1'], 'result' => ['status' => 'ready']];
    $id = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'same-client', 'messages' => [$message]])->assertOk()->json('id');
    $this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->assertJsonPath('messages.0.presentation', $message['presentation']);
    $this->actingAs(Admin::factory()->create(['role_id' => $this->owner->role_id]), 'admin');
    $this->getJson(route('ai-agent.conversations.show', $id))->assertNotFound();
    $this->deleteJson(route('ai-agent.conversations.destroy', $id))->assertOk()->assertJsonPath('deleted', false);
    $other = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'same-client', 'messages' => [historyMessage('other')]])->assertOk()->json('id');
    expect($other)->not->toBe($id);
    Http::assertNothingSent();
});

it('honors history ACL even when persistence is enabled', function () {
    config(['ai-agent.history.enabled' => true]);
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog.products']]);
    $this->actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');
    $this->postJson(route('ai-agent.conversations.store'), ['messages' => [historyMessage('reply')]])->assertForbidden();
});

it('keeps regenerated replies deleted when a delayed tab saves them again', function (bool $deleteFirst) {
    config(['ai-agent.history.enabled' => true]);
    $old = ['conversation_id' => 'regenerate-fixture', 'messages' => [historyMessage('old')]];
    if (! $deleteFirst) {
        $this->postJson(route('ai-agent.conversations.store'), $old)->assertOk();
    }
    $id = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'regenerate-fixture', 'messages' => [historyMessage('new')], 'deleted_message_ids' => ['old']])->assertOk()->json('id');
    $this->postJson(route('ai-agent.conversations.store'), $old)->assertOk();
    $this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.message_id', 'new')->assertJsonPath('deleted_message_ids.0', 'old');
})->with([false, true]);

it('allows a deletion-only save before the replacement reply exists', function () {
    config(['ai-agent.history.enabled' => true]);
    $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'empty-retry', 'messages' => [], 'deleted_message_ids' => ['old']])->assertOk();
});

it('upgrades legacy identities so reopening and regenerating do not duplicate or retain the old reply', function () {
    config(['ai-agent.history.enabled' => true]);
    $id = DB::table('ai_agent_conversations')->insertGetId(['user_id' => $this->owner->id, 'title' => 'Legacy conversation', 'created_at' => now(), 'updated_at' => now()]);
    $messageId = DB::table('ai_agent_messages')->insertGetId(['conversation_id' => $id, 'role' => 'assistant', 'content' => 'Legacy reply']);
    $migration = require __DIR__.'/../../Database/Migration/2026_10_14_000005_add_message_deletion_identity.php';
    $migration->down();
    $migration->up();
    $response = $this->getJson(route('ai-agent.conversations.show', $id))->assertOk();
    $clientId = $response->json('conversation.client_id');
    $identity = $response->json('messages.0.message_id');
    expect($clientId)->not->toBeNull();
    expect($identity)->not->toBeNull();
    $payload = ['conversation_id' => $clientId, 'messages' => [historyMessage($identity, 'Legacy reply')]];
    $this->postJson(route('ai-agent.conversations.store'), $payload)->assertOk()->assertJsonPath('id', $id);
    expect(DB::table('ai_agent_messages')->where('conversation_id', $id)->count())->toBe(1);
    expect(DB::table('ai_agent_messages')->where('id', $messageId)->value('content'))->toBe('Legacy reply');
    $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => $clientId, 'messages' => [], 'deleted_message_ids' => [$identity]])->assertOk();
    $this->postJson(route('ai-agent.conversations.store'), $payload)->assertOk();
    $this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->assertJsonCount(0, 'messages');
});

it('merges stale snapshots without losing later messages or reviving handled confirmations', function () {
    config(['ai-agent.history.enabled' => true]);
    $first = historyMessage('first') + ['_confirmed' => true, 'result' => ['status' => 'done']];
    $id = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'merge-fixture', 'metadata' => ['owner_field' => 1], 'messages' => [$first, historyMessage('second')]])->assertOk()->json('id');
    $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'merge-fixture', 'metadata' => ['owner_field' => 2, 'new_field' => 3], 'messages' => [historyMessage('first') + ['_confirmed' => false], historyMessage('first', 'Changed identity')]])->assertOk();
    $this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->assertJsonCount(2, 'messages')
        ->assertJsonPath('messages.0._confirmed', true)->assertJsonPath('messages.0.result.status', 'done');
    expect(json_decode(DB::table('ai_agent_conversations')->where('id', $id)->value('metadata'), true))->toEqual(['owner_field' => 1, 'new_field' => 3]);
});

it('batches message reads and writes instead of issuing queries per message', function () {
    config(['ai-agent.history.enabled' => true]);
    $counts = [];
    foreach ([1, 100] as $size) {
        $messages = array_map(fn ($i) => historyMessage('message-'.$i), range(1, $size));
        foreach ([false, true] as $repeat) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'batch-'.$size, 'messages' => $messages])->assertOk();
            $queries = array_filter(DB::getQueryLog(), fn ($query) => str_contains($query['query'], 'ai_agent_messages'));
            $counts[] = count($queries);
            DB::disableQueryLog();
        }
    }
    expect(max($counts))->toBeLessThanOrEqual(2);
    expect(Schema::hasIndex('ai_agent_messages', ['conversation_id', 'message_id'], 'unique'))->toBeTrue();
});

it('rejects invalid display fields and oversized metadata without partial writes', function () {
    config(['ai-agent.history.enabled' => true]);
    foreach ([['product_url' => 'javascript:alert(1)'], ['result' => ['__proto__' => []]], ['_confirmed' => 'yes']] as $invalid) {
        $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'invalid-history', 'messages' => [historyMessage('reply') + $invalid]])->assertUnprocessable();
    }
    $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'invalid-history', 'metadata' => ['large' => str_repeat('x', 65537)], 'messages' => [historyMessage('reply')]])->assertUnprocessable();
    expect(DB::table('ai_agent_conversations')->where('client_id', 'invalid-history')->exists())->toBeFalse();
});

it('lets owners delete stored history after the setting is turned off', function () {
    $id = DB::table('ai_agent_conversations')->insertGetId(['user_id' => $this->owner->id, 'title' => 'Kept', 'created_at' => now(), 'updated_at' => now()]);
    expect(config('ai-agent.history.enabled'))->toBeFalse();

    $this->deleteJson(route('ai-agent.conversations.destroy', $id))->assertOk()->assertJsonPath('deleted', true);
    expect(DB::table('ai_agent_conversations')->where('id', $id)->exists())->toBeFalse();
});

it('still requires the chat ACL to delete stored history', function () {
    $id = DB::table('ai_agent_conversations')->insertGetId(['user_id' => $this->owner->id, 'title' => 'Kept', 'created_at' => now(), 'updated_at' => now()]);
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => ['catalog.products']]);
    $this->actingAs(Admin::factory()->create(['role_id' => $role->id]), 'admin');

    $this->deleteJson(route('ai-agent.conversations.destroy', $id))->assertForbidden();
    expect(DB::table('ai_agent_conversations')->where('id', $id)->exists())->toBeTrue();
});

it('returns only the latest configured number of messages in order', function () {
    config(['ai-agent.history.enabled' => true, 'ai-agent.history.max_messages' => 3]);
    $messages = array_map(fn ($i) => historyMessage('message-'.$i, 'Reply '.$i), range(1, 5));
    $id = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'capped', 'messages' => $messages])->assertOk()->json('id');

    $response = $this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->assertJsonCount(3, 'messages');
    expect(array_column($response->json('messages'), 'message_id'))->toBe(['message-3', 'message-4', 'message-5']);
});

it('prunes deletion markers beyond the retained window', function () {
    config(['ai-agent.history.enabled' => true, 'ai-agent.history.max_messages' => 2]);
    foreach (['old-1', 'old-2', 'old-3', 'old-4'] as $deleted) {
        $id = $this->postJson(route('ai-agent.conversations.store'), ['conversation_id' => 'markers', 'messages' => [], 'deleted_message_ids' => [$deleted]])->assertOk()->json('id');
    }

    expect(DB::table('ai_agent_messages')->where('conversation_id', $id)->whereNotNull('deleted_at')->pluck('message_id')->sort()->values()->all())
        ->toBe(['old-3', 'old-4']);
    expect($this->getJson(route('ai-agent.conversations.show', $id))->assertOk()->json('deleted_message_ids'))
        ->toEqualCanonicalizing(['old-3', 'old-4']);
});
