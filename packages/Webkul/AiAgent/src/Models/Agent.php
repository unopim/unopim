<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\AiAgent\Contracts\Agent as AgentContract;
use Webkul\AiAgent\Presenters\AgentPresenter;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Agent extends Model implements AgentContract, AuditableContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Tags for history/audit.
     *
     * @var array
     */
    protected $historyTags = ['ai-agent'];

    /**
     * @var string
     */
    protected $table = 'ai_agent_agents';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'systemPrompt',
        'pipeline',
        'credentialId',
        'maxTokens',
        'temperature',
        'extras',
        'status',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'extras'      => 'array',
        'pipeline'    => 'array',
        'status'      => 'boolean',
        'maxTokens'   => 'integer',
        'temperature' => 'float',
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
        'name',
        'description',
        'systemPrompt',
        'pipeline',
        'status',
    ];

    /**
     * Get the credential associated with this agent.
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class, 'credentialId');
    }

    /**
     * Get executions for this agent.
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AgentExecution::class, 'agentId');
    }

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'common' => AgentPresenter::class,
        ];
    }
}
