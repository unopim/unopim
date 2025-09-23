<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Webhook\Presenters\SettingsPresenter;

class Settings extends Model implements HistoryAuditable, PresentableHistoryInterface
{
    use HasFactory, HistoryTrait;

    protected $auditExclude = ['value', 'extras'];

    protected $historyTags = ['webhook_history'];

    protected $table = 'webhook_settings';

    protected $id = 1;

    public $timestamps = true;

    protected $fillable = [
        'field',
        'value',
        'extra',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public static function getPresenters(): array
    {
        return [
            'common' => SettingsPresenter::class,
        ];
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->id;
    }
}
