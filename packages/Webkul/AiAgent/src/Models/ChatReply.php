<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A single assistant reply that is generated asynchronously on a queue worker.
 * Its text is written here as it streams so the chat widget can poll for it and
 * resume across a full page reload. Rows are short-lived (see ChatReplyJob /
 * the cleanup sweep) — this is transport state, not the conversation history.
 */
class ChatReply extends Model
{
    use HasUuids;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'ai_agent_chat_replies';

    protected $fillable = [
        'id',
        'user_id',
        'session_id',
        'status',
        'content',
        'actions',
        'agent_status',
        'tokens_used',
        'error',
    ];

    protected $casts = [
        'actions'     => 'array',
        'tokens_used' => 'integer',
    ];

    public const STATUS_QUEUED = 'queued';

    public const STATUS_GENERATING = 'generating';

    public const STATUS_DONE = 'done';

    public const STATUS_ERROR = 'error';

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_DONE, self::STATUS_ERROR], true);
    }
}
