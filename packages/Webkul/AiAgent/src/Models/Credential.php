<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\AiAgent\Contracts\Credential as CredentialContract;
use Webkul\AiAgent\Presenters\CredentialPresenter;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

class Credential extends Model implements AuditableContract, CredentialContract, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    /**
     * Tags for history/audit.
     *
     * @var array
     */
    protected $historyTags = ['ai-agent-credential'];

    /**
     * Table name — always use wk_ prefix.
     *
     * @var string
     */
    protected $table = 'ai_agent_credentials';

    /**
     * Fillable attributes.
     *
     * @var array
     */
    protected $fillable = [
        'label',
        'provider',
        'apiUrl',
        'apiKey',
        'model',
        'extras',
        'status',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    /**
     * Attributes hidden from JSON serialization — prevent API key leakage.
     *
     * @var array
     */
    protected $hidden = [
        'apiKey',
    ];

    /**
     * Casts.
     *
     * @var array
     */
    protected $casts = [
        'extras' => 'array',
        'status' => 'boolean',
        'apiKey' => 'encrypted',
    ];

    /**
     * Fields excluded from history audit — sensitive values.
     *
     * @var array
     */
    protected $auditExclude = [
        'apiKey',
    ];

    /**
     * History auditable attributes.
     *
     * @var array
     */
    protected $historyAuditable = [
        'label',
        'provider',
        'apiUrl',
        'model',
        'status',
    ];

    /**
     * {@inheritdoc}
     */
    public static function getPresenters(): array
    {
        return [
            'common' => CredentialPresenter::class,
        ];
    }
}
