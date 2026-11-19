<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\AiAgent\Contracts\Conversation as ConversationContract;

#[Fillable([
    'user_id',
    'client_id',
    'title',
    'metadata',
])]
#[Table(name: 'ai_agent_conversations')]
class Conversation extends Model implements ConversationContract
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
