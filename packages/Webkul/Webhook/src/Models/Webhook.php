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
    use HistoryTrait {
        transformAudit as protected transformHistoryAudit;
    }

    public const string MASKED_HEADER_VALUE = '••••••••';

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

    /**
     * Mask custom header values before they reach the audit trail, keeping the header names.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function transformAudit(array $data): array
    {
        $data = $this->transformHistoryAudit($data);

        foreach (['old_values', 'new_values'] as $bucket) {
            if (isset($data[$bucket]['headers'])) {
                $data[$bucket]['headers'] = static::maskHeaders($data[$bucket]['headers']);
            }
        }

        return $data;
    }

    /**
     * Replace every value in a JSON header map with a mask; anything that is not a JSON map is returned as is.
     */
    public static function maskHeaders(mixed $headers): mixed
    {
        $decoded = is_string($headers) ? json_decode($headers, true) : null;

        if (! is_array($decoded)) {
            return $headers;
        }

        return json_encode(array_map(fn (): string => self::MASKED_HEADER_VALUE, $decoded), JSON_UNESCAPED_UNICODE);
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
