<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Webkul\Publication\Contracts\PublicationVersionPayload as PublicationVersionPayloadContract;
use Webkul\Publication\Exceptions\ImmutableVersionException;

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
            get: function (mixed $value): ?array {
                $binary = static::decodeBinary($value);

                if ($binary === null) {
                    return null;
                }

                $json = gzdecode($binary);

                if ($json === false) {
                    throw new RuntimeException('Publication version payload '.$this->getKey().' is corrupt: gzip decompression failed.');
                }

                return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            },
            set: function (?array $value): ?string {
                if ($value === null) {
                    return null;
                }

                $gzip = gzencode(json_encode($value, JSON_THROW_ON_ERROR), 9);

                return DB::getDriverName() === 'pgsql'
                    ? '\x'.bin2hex($gzip)
                    : $gzip;
            },
        )->shouldCache();
    }

    protected static function decodeBinary(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            /**
             * A pgsql bytea arrives as a stream, and reading it moves the pointer:
             * without the rewind the second consumer of the same instance (the
             * publish listeners after the page has rendered it, say) reads an empty
             * string and sees a payload that looks absent.
             */
            @rewind($value);

            $value = stream_get_contents($value);
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, '\x')) {
            $decoded = @hex2bin(substr($value, 2));

            if ($decoded !== false) {
                return $decoded;
            }
        }

        return $value;
    }
}
