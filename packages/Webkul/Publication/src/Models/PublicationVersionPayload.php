<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;
use Webkul\Publication\Contracts\PublicationVersionPayload as PublicationVersionPayloadContract;
use Webkul\Publication\Exceptions\ImmutableVersionException;

/**
 * Derived storage for `PublicationVersion::payload`, externalised so the
 * attested-metadata table stays thin (see the 000003 migration). `payload` is
 * gzip-9 compressed JSON; only PublicationVersion::redact() may ever write to
 * it after creation, and only to null.
 */
#[Fillable([
    'publication_version_id',
    'payload',
    'archive_path',
])]
#[Table(name: 'publication_version_payloads')]
class PublicationVersionPayload extends Model implements PublicationVersionPayloadContract
{
    protected $primaryKey = 'publication_version_id';

    public $incrementing = false;

    protected $keyType = 'int';

    protected static function booted(): void
    {
        static::updating(function (self $payload): void {
            $dirty = $payload->getDirty();

            $isSanctionedRedaction = array_keys($dirty) === ['payload']
                && $payload->getOriginal('payload') !== null
                && $dirty['payload'] === null;

            if (! $isSanctionedRedaction) {
                throw new ImmutableVersionException(
                    'Publication version payload '.$payload->getKey().' is immutable outside of a one-way redaction.'
                );
            }
        });

        static::deleting(function (self $payload): void {
            throw new ImmutableVersionException(
                'Publication version payload '.$payload->getKey().' cannot be deleted directly; it is only removed by cascading from its version.'
            );
        });
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PublicationVersionProxy::modelClass(), 'publication_version_id');
    }

    /**
     * @return Attribute<array<string, mixed>|null, array<string, mixed>|null>
     */
    protected function payload(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?array {
                if ($value === null) {
                    return null;
                }

                $json = gzdecode($value);

                if ($json === false) {
                    throw new RuntimeException('Publication version payload '.$this->getKey().' is corrupt: gzip decompression failed.');
                }

                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            },
            set: fn (?array $value): ?string => $value === null ? null : gzencode(json_encode($value, JSON_THROW_ON_ERROR), 9),
        );
    }
}
