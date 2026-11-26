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

    public const string MASKED_VALUE = '••••••••';

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
     * Mask custom header values and URL credentials before they reach the audit trail, keeping header names and readable URL parts.
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

            if (isset($data[$bucket]['url'])) {
                $data[$bucket]['url'] = static::maskUrl($data[$bucket]['url']);
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

        return json_encode(array_map(fn (): string => self::MASKED_VALUE, $decoded), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mask everything in a URL that can carry a credential: userinfo, every path segment and every query value.
     *
     * Scheme, host, port, query keys and the fragment stay readable. A string that is not an absolute URL is masked whole.
     */
    public static function maskUrl(mixed $url): mixed
    {
        if (! is_string($url)) {
            return $url;
        }

        if (! preg_match('#^([a-z][a-z0-9+.-]*://)([^/?\#]*@)?([^/?\#]*)([^?\#]*)(\?[^\#]*)?(\#.*)?$#is', $url, $parts)) {
            return self::MASKED_VALUE;
        }

        return $parts[1]
            .(($parts[2] ?? '') !== '' ? self::MASKED_VALUE.'@' : '')
            .$parts[3]
            .preg_replace('#[^/]+#', self::MASKED_VALUE, $parts[4])
            .static::maskQuery($parts[5] ?? '')
            .($parts[6] ?? '');
    }

    /**
     * Mask every value of a query string, keeping its keys; an item without a key is masked whole.
     */
    protected static function maskQuery(string $query): string
    {
        if ($query === '' || $query === '?') {
            return $query;
        }

        $items = array_map(
            fn (string $item): string => match (true) {
                $item === ''               => '',
                str_contains($item, '=')   => strstr($item, '=', true).'='.self::MASKED_VALUE,
                default                    => self::MASKED_VALUE,
            },
            explode('&', substr($query, 1)),
        );

        return '?'.implode('&', $items);
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
