<?php

namespace Webkul\AiAgent\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Webkul\AiAgent\Contracts\Credential as CredentialContract;
use Webkul\AiAgent\Presenters\CredentialPresenter;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;

#[Fillable([
    'label',
    'provider',
    'apiUrl',
    'apiKey',
    'model',
    'extras',
    'status',
])]
#[Hidden([
    'apiKey',
])]
#[Table(name: 'ai_agent_credentials')]
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

    /**
     * Casts.
     */
    protected function casts(): array
    {
        return [
            'extras' => 'array',
            'status' => 'boolean',
            'apiKey' => 'encrypted',
        ];
    }
}
