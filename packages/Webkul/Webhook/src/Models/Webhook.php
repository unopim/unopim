<?php

namespace Webkul\Webhook\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Webhook\Presenters\WebhookPresenter;

#[Fillable([
    'name',
    'url',
    'is_active',
    'events',
    'secret',
    'headers',
    'extra',
])]
#[Table(name: 'webhooks')]
class Webhook extends Model implements HistoryAuditable, PresentableHistoryInterface
{
    use HistoryTrait;

    protected $auditExclude = ['secret'];

    protected $historyTags = ['webhooks'];

    public $timestamps = true;

    public static function getPresenters(): array
    {
        return [
            'common' => WebhookPresenter::class,
        ];
    }

    public function getPrimaryModelIdForHistory(): int
    {
        return $this->id;
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'events'    => 'array',
            'headers'   => 'array',
            'extra'     => 'array',
        ];
    }

    /**
     * Whether this webhook is subscribed to the given event key.
     *
     * Read through getAttribute(): a bare $this->events matches Rector's
     * Laravel rename of the legacy Model::$events property and gets rewritten
     * to $dispatchesEvents, which silently empties the subscription list.
     */
    public function subscribesTo(string $event): bool
    {
        return in_array($event, (array) $this->getAttribute('events'), true);
    }
}
