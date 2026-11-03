<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Webkul\AiAgent\Contracts\ChatReply as ChatReplyContract;

/**
 * One assistant reply generated asynchronously on a queue worker.
 *
 * Its text is written here as it streams so the chat widget can poll it and
 * resume across a full page reload. Rows are short-lived transport state, not
 * the conversation history.
 */
#[Fillable([
    'user_id',
    'conversation_id',
    'status',
    'content',
    'actions',
    'agent_status',
    'tokens_used',
    'error',
])]
#[Table(name: 'ai_agent_chat_replies')]
class ChatReply extends Model implements ChatReplyContract
{
    use HasUuids;

    public const STATUS_QUEUED = 'queued';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_DONE = 'done';

    public const STATUS_ERROR = 'error';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Whether the reply reached a final state and polling can stop.
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_ERROR, self::STATUS_CANCELLED], true);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actions'     => 'array',
            'tokens_used' => 'integer',
        ];
    }
}
