<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\AiAgent\Chat\MessageDisplay;
use Webkul\AiAgent\Chat\ResultRendererRegistry;
use Webkul\AiAgent\Http\Requests\ConversationRequest;
use Webkul\AiAgent\Http\Requests\StoreConversationRequest;

class ConversationController extends Controller
{
    public function index(ConversationRequest $request): JsonResponse
    {
        $conversations = DB::table('ai_agent_conversations')
            ->where('user_id', auth()->guard('admin')->id())
            ->latest('updated_at')->limit(50)
            ->get(['id', 'client_id', 'title', 'metadata', 'created_at', 'updated_at']);

        return new JsonResponse(['conversations' => $conversations]);
    }

    public function show(ConversationRequest $request, int $id): JsonResponse
    {
        $conversation = DB::table('ai_agent_conversations')->where('id', $id)
            ->where('user_id', auth()->guard('admin')->id())->first();

        if (! $conversation) {
            return new JsonResponse(['error' => trans('ai-agent::app.common.conversation-not-found')], 404);
        }

        $messages = DB::table('ai_agent_messages')
            ->where('conversation_id', $id)->whereNull('deleted_at')->orderBy('id')
            ->get(['role', 'content', 'tool_calls', 'tokens_used', 'created_at', 'message_id', 'request_id', 'presentation', 'display_data'])
            ->map(function ($message) {
                $message->presentation = app(ResultRendererRegistry::class)->descriptor(json_decode($message->presentation ?? 'null', true));
                foreach (json_decode($message->display_data ?? '{}', true) ?: [] as $key => $value) {
                    $message->{$key} = $value;
                }
                unset($message->display_data);

                return $message;
            });

        return new JsonResponse([
            'conversation'        => $conversation, 'messages' => $messages,
            'deleted_message_ids' => DB::table('ai_agent_messages')->where('conversation_id', $id)->whereNotNull('deleted_at')->pluck('message_id'),
        ]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $userId = auth()->guard('admin')->id();
        $conversationId = DB::transaction(function () use ($request, $userId) {
            $clientId = $request->input('conversation_id') ?: (string) Str::uuid();
            $now = now();
            DB::table('ai_agent_conversations')->upsert([
                'user_id'    => $userId, 'client_id' => $clientId,
                'title'      => $request->input('title') ?: trans('ai-agent::app.widget.session-default-name').' '.$now->format('M d, H:i'),
                'metadata'   => json_encode($request->input('metadata') ?? new \stdClass),
                'created_at' => $now, 'updated_at' => $now,
            ], ['user_id', 'client_id'], ['updated_at']);

            $conversation = DB::table('ai_agent_conversations')->where('user_id', $userId)->where('client_id', $clientId)->lockForUpdate()->first();
            $id = $conversation->id;
            $metadata = (json_decode($conversation->metadata ?? '{}', true) ?: []) + ($request->input('metadata') ?? []);
            DB::table('ai_agent_conversations')->where('id', $id)->update(['metadata' => json_encode($metadata ?: new \stdClass)]);

            $deleted = array_map(fn ($messageId) => [
                'conversation_id' => $id, 'message_id' => $messageId,
                'role'            => 'assistant', 'content' => '', 'tokens_used' => 0, 'created_at' => $now, 'deleted_at' => $now,
            ], $request->input('deleted_message_ids', []));
            if ($deleted !== []) {
                DB::table('ai_agent_messages')->upsert($deleted, ['conversation_id', 'message_id'], ['deleted_at']);
            }

            $incoming = [];
            foreach ($request->input('messages') as $index => $message) {
                $messageId = $message['message_id'] ?? hash('sha256', json_encode([$index, $message['role'], $message['content']]));
                $incoming[$messageId] ??= $message;
            }
            $existing = DB::table('ai_agent_messages')->where('conversation_id', $id)->whereIn('message_id', array_keys($incoming))->get()->keyBy('message_id');
            $rows = [];
            foreach ($incoming as $messageId => $message) {
                $saved = $existing->get($messageId);
                if ($saved && ($saved->deleted_at !== null || $saved->role !== $message['role'] || $saved->content !== $message['content'])) {
                    continue;
                }
                $display = app(MessageDisplay::class)->normalize($message);
                if ($saved) {
                    $previous = json_decode($saved->display_data ?? '{}', true) ?: [];
                    $display = $previous + $display;
                    if (($previous['_confirmed'] ?? false) || ($message['_confirmed'] ?? false)) {
                        $display['_confirmed'] = true;
                    }
                }
                $rows[] = [
                    'conversation_id' => $id, 'message_id' => $messageId,
                    'role'            => $message['role'], 'content' => $message['content'], 'request_id' => $message['request_id'] ?? null,
                    'tool_calls'      => isset($message['tool_calls']) ? json_encode($message['tool_calls']) : null,
                    'tokens_used'     => $message['tokens_used'] ?? 0,
                    'presentation'    => json_encode(app(ResultRendererRegistry::class)->descriptor($message['presentation'] ?? null)),
                    'display_data'    => json_encode($display), 'created_at' => $now,
                ];
            }
            if ($rows !== []) {
                DB::table('ai_agent_messages')->upsert($rows, ['conversation_id', 'message_id'], ['display_data']);
            }

            return $id;
        });

        return new JsonResponse(['id' => $conversationId, 'saved' => true]);
    }

    public function destroy(ConversationRequest $request, int $id): JsonResponse
    {
        $deleted = DB::table('ai_agent_conversations')->where('id', $id)
            ->where('user_id', auth()->guard('admin')->id())->delete();

        return new JsonResponse(['deleted' => (bool) $deleted]);
    }
}
