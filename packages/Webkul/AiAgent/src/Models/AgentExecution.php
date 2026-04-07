<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\AiAgent\Contracts\AgentExecution as AgentExecutionContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class AgentExecution extends Model implements AgentExecutionContract, AuditableContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Tags for history/audit.
     *
     * @var array
     */
    protected $historyTags = ['ai-agent-execution'];

    /**
     * @var string
     */
    protected $table = 'ai_agent_executions';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'agentId',
        'credentialId',
        'instruction',
        'output',
        'tokensUsed',
        'executionTimeMs',
        'status',
        'error',
        'extras',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'extras'          => 'array',
        'tokensUsed'      => 'integer',
        'executionTimeMs' => 'integer',
    ];

    /**
     * Fields excluded from history audit.
     *
     * @var array
     */
    protected $auditExclude = [];

    /**
     * History auditable attributes.
     *
     * @var array
     */
    protected $historyAuditable = [
        'status',
        'tokensUsed',
        'executionTimeMs',
    ];

    /**
     * Get the agent that owns this execution.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agentId');
    }

    /**
     * Get the credential used for this execution.
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'credentialId');
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [];
    }
}
