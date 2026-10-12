<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Webhook\Presenters\SettingsPresenter;

#[Fillable([
    'field',
    'value',
    'extra',
])]
#[Table(name: 'webhook_settings')]
class WebhookSetting extends Model implements HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $auditExclude = ['value', 'extras'];

    protected $historyTags = ['webhook_settings'];

    public $timestamps = true;

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
        return 1;
    }

    protected function casts(): array
    {
        return [
            'extra' => 'array',
        ];
    }
}
