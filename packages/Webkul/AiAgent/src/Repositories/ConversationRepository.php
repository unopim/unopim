<?php

namespace Webkul\AiAgent\Repositories;

use Illuminate\Container\Container;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use stdClass;
use Webkul\AiAgent\Chat\MessageDisplay;
use Webkul\AiAgent\Chat\ResultRendererRegistry;
use Webkul\AiAgent\Models\Conversation;
use Webkul\Core\Eloquent\Repository;

class ConversationRepository extends Repository
{
    public function __construct(
        protected MessageDisplay $messageDisplay,
        protected ResultRendererRegistry $resultRenderers,
        Container $container,
    ) {
        parent::__construct($container);
    }

    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return Conversation::class;
    }

    /**
     * The user's most recently updated conversations.
     */
    public function latestForUser(int $userId, int $limit = 50): Collection
    {
        return $this->conversations()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit($limit)
            ->get(['id', 'client_id', 'title', 'metadata', 'created_at', 'updated_at']);
    }

    /**
     * Find a conversation owned by the user.
     */
    public function findForUser(int $id, int $userId): ?stdClass
    {
        return $this->conversations()->where('id', $id)->where('user_id', $userId)->first();
    }

    /**
     * The latest live messages of a conversation, oldest first.
     */
    public function latestMessages(int $conversationId, int $limit): Collection
    {
        return $this->messages()
            ->where('conversation_id', $conversationId)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['role', 'content', 'tool_calls', 'tokens_used', 'created_at', 'message_id', 'request_id', 'presentation', 'display_data'])
            ->reverse()
            ->values();
    }

    /**
     * Message ids of the latest deletion markers in a conversation.
     */
    public function deletedMessageIds(int $conversationId, int $limit): Collection
    {
        return $this->messages()
            ->where('conversation_id', $conversationId)
            ->whereNotNull('deleted_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->pluck('message_id');
    }

    /**
     * Merge a client snapshot into the user's conversation and return its id.
     *
     * Stored metadata wins over the snapshot's so a stale tab cannot overwrite
     * keys another tab already saved; the snapshot only adds new keys.
     *
     * @param  array{conversation_id?: string|null, title?: string|null, metadata?: array<string, mixed>|null, messages: list<array<string, mixed>>, deleted_message_ids?: list<string>}  $snapshot
     */
    public function saveSnapshot(int $userId, array $snapshot, int $retainedMarkers): int
    {
        return DB::transaction(function () use ($userId, $snapshot, $retainedMarkers): int {
            $clientId = ($snapshot['conversation_id'] ?? null) ?: (string) Str::uuid();
            $now = now();

            $this->conversations()->upsert([
                'user_id'    => $userId,
                'client_id'  => $clientId,
                'title'      => ($snapshot['title'] ?? null) ?: trans('ai-agent::app.widget.session-default-name').' '.$now->format('M d, H:i'),
                'metadata'   => json_encode($snapshot['metadata'] ?? new stdClass),
                'created_at' => $now,
                'updated_at' => $now,
            ], ['user_id', 'client_id'], ['updated_at']);

            $conversation = $this->conversations()->where('user_id', $userId)->where('client_id', $clientId)->lockForUpdate()->first();
            $id = $conversation->id;

            $metadata = (json_decode($conversation->metadata ?? '{}', true) ?: []) + ($snapshot['metadata'] ?? []);
            $this->conversations()->where('id', $id)->update(['metadata' => json_encode($metadata ?: new stdClass)]);

            $this->markDeleted($id, $snapshot['deleted_message_ids'] ?? [], $now, $retainedMarkers);
            $this->mergeMessages($id, $snapshot['messages'], $now);

            return $id;
        });
    }

    /**
     * Delete a conversation owned by the user.
     */
    public function deleteForUser(int $id, int $userId): bool
    {
        return (bool) $this->conversations()->where('id', $id)->where('user_id', $userId)->delete();
    }

    /**
     * Record deletion markers so a delayed save cannot revive the messages,
     * keeping only the newest markers within the retained window.
     *
     * @param  list<string>  $messageIds
     */
    protected function markDeleted(int $conversationId, array $messageIds, mixed $now, int $retainedMarkers): void
    {
        if ($messageIds === []) {
            return;
        }

        $this->messages()->upsert(array_map(fn (string $messageId): array => [
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'role'            => 'assistant',
            'content'         => '',
            'tokens_used'     => 0,
            'created_at'      => $now,
            'deleted_at'      => $now,
        ], $messageIds), ['conversation_id', 'message_id'], ['deleted_at']);

        $cutoff = $this->messages()
            ->where('conversation_id', $conversationId)
            ->whereNotNull('deleted_at')
            ->orderByDesc('id')
            ->skip($retainedMarkers)
            ->value('id');

        if ($cutoff !== null) {
            $this->messages()
                ->where('conversation_id', $conversationId)
                ->whereNotNull('deleted_at')
                ->where('id', '<=', $cutoff)
                ->delete();
        }
    }

    /**
     * Insert new messages and merge display data into unchanged existing ones
     * with one read and one write, skipping deleted or rewritten identities.
     *
     * @param  list<array<string, mixed>>  $messages
     */
    protected function mergeMessages(int $conversationId, array $messages, mixed $now): void
    {
        $incoming = [];

        foreach ($messages as $index => $message) {
            $messageId = $message['message_id'] ?? hash('sha256', json_encode([$index, $message['role'], $message['content']]));
            $incoming[$messageId] ??= $message;
        }

        $existing = $this->messages()
            ->where('conversation_id', $conversationId)
            ->whereIn('message_id', array_keys($incoming))
            ->get()
            ->keyBy('message_id');

        $rows = [];

        foreach ($incoming as $messageId => $message) {
            $saved = $existing->get($messageId);

            if ($saved && ($saved->deleted_at !== null || $saved->role !== $message['role'] || $saved->content !== $message['content'])) {
                continue;
            }

            $display = $this->messageDisplay->normalize($message);

            if ($saved) {
                $previous = json_decode($saved->display_data ?? '{}', true) ?: [];
                $display = $previous + $display;

                if (($previous['_confirmed'] ?? false) || ($message['_confirmed'] ?? false)) {
                    $display['_confirmed'] = true;
                }
            }

            $rows[] = [
                'conversation_id' => $conversationId,
                'message_id'      => $messageId,
                'role'            => $message['role'],
                'content'         => $message['content'],
                'request_id'      => $message['request_id'] ?? null,
                'tool_calls'      => isset($message['tool_calls']) ? json_encode($message['tool_calls']) : null,
                'tokens_used'     => $message['tokens_used'] ?? 0,
                'presentation'    => json_encode($this->resultRenderers->descriptor($message['presentation'] ?? null)),
                'display_data'    => json_encode($display),
                'created_at'      => $now,
            ];
        }

        if ($rows !== []) {
            $this->messages()->upsert($rows, ['conversation_id', 'message_id'], ['display_data']);
        }
    }

    protected function conversations(): Builder
    {
        return DB::table('ai_agent_conversations');
    }

    protected function messages(): Builder
    {
        return DB::table('ai_agent_messages');
    }
}
