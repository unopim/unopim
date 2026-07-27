<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Publication\Contracts\PublicationVersion as PublicationVersionContract;
use Webkul\Publication\Database\Factories\PublicationVersionFactory;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\User\Models\AdminProxy;

/**
 * The immutability guard below only fires on instance-level save()/delete().
 * Bulk query-builder writes and withoutEvents()/saveQuietly() bypass Eloquent
 * events and will silently mutate or destroy attested versions.
 */
#[Fillable([
    'publication_id',
    'locale_id',
    'version',
    'payload',
    'checksum',
    'is_current',
    'published_at',
    'published_by_id',
    'redacted_at',
    'redacted_by_id',
    'redacted_reason',
])]
#[Table(name: 'publication_versions')]
class PublicationVersion extends Model implements PublicationVersionContract
{
    use HasFactory;

    /**
     * Columns that may change after a version is written; everything else is
     * sealed once published. Redaction columns are handled separately by
     * isSanctionedRedaction() since that's a one-way (null -> set) transition,
     * not an always-mutable column.
     */
    private const MUTABLE_AFTER_PUBLISH = ['is_current', 'updated_at'];

    /**
     * `payload` lives in `publication_version_payloads`, not this table (see
     * the 000003 migration). Holds the in-flight value between construction
     * and the `created` event that persists it, keeping the array API below
     * unchanged for existing callers.
     */
    private ?array $pendingPayload = null;

    private bool $hasPendingPayload = false;

    protected function casts(): array
    {
        return [
            'is_current'    => 'boolean',
            'published_at'  => 'datetime',
            'version'       => 'integer',
            'redacted_at'   => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            $dirty = $version->getDirty();

            if (self::isSanctionedRedaction($version, $dirty)) {
                return;
            }

            $touched = array_diff(array_keys($dirty), self::MUTABLE_AFTER_PUBLISH);

            if ($touched !== []) {
                throw new ImmutableVersionException(
                    'Published version '.$version->id.' is immutable; attempted to change: '.implode(', ', $touched)
                );
            }
        });

        static::deleting(function (self $version): void {
            throw new ImmutableVersionException('Published version '.$version->id.' cannot be deleted.');
        });

        static::created(function (self $version): void {
            if (! $version->hasPendingPayload) {
                return;
            }

            $version->payloadRecord()->create(['payload' => $version->pendingPayload]);

            $version->hasPendingPayload = false;
            $version->pendingPayload = null;
            $version->unsetRelation('payloadRecord');
        });
    }

    /**
     * The one sanctioned exception to immutability (GDPR Art. 17 erasure via
     * redact()): redacted_at moving from null to a value, plus redacted_by_id/
     * redacted_reason/updated_at, never in reverse. Checking getOriginal()
     * makes it one-way — a second attempt falls through to the immutability
     * check below and throws.
     */
    private static function isSanctionedRedaction(self $version, array $dirty): bool
    {
        $redactionColumns = ['redacted_at', 'redacted_by_id', 'redacted_reason'];

        if (array_intersect(array_keys($dirty), $redactionColumns) === []) {
            return false;
        }

        return $version->getOriginal('redacted_at') === null
            && ($dirty['redacted_at'] ?? null) !== null
            && array_diff(array_keys($dirty), [...$redactionColumns, 'updated_at']) === [];
    }

    public function setAttribute($key, $value)
    {
        if ($key === 'payload') {
            if ($this->exists) {
                // Reassigning payload on a persisted version is a tamper attempt;
                // redact() is the only legitimate write path and never touches it.
                throw new ImmutableVersionException(
                    'Published version '.$this->id.' is immutable; attempted to change: payload'
                );
            }

            $this->pendingPayload = $value;
            $this->hasPendingPayload = true;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    public function getAttribute($key)
    {
        if ($key === 'payload') {
            return $this->resolvePayload();
        }

        return parent::getAttribute($key);
    }

    private function resolvePayload(): ?array
    {
        if ($this->hasPendingPayload) {
            return $this->pendingPayload;
        }

        return $this->payloadRecord?->payload;
    }

    public function payloadRecord(): HasOne
    {
        return $this->hasOne(PublicationVersionPayloadProxy::modelClass(), 'publication_version_id');
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(LocaleProxy::modelClass());
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'published_by_id');
    }

    public function redactedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'redacted_by_id');
    }

    public function markSuperseded(): void
    {
        $this->forceFill(['is_current' => false])->save();
    }

    /**
     * GDPR Art. 17 erasure. Nulls the payload but keeps the checksum so the
     * audit trail still proves what was removed. Irreversible.
     */
    public function redact(int $redactedById, string $reason): void
    {
        if ($this->redacted_at !== null) {
            throw new ImmutableVersionException(
                'Published version '.$this->id.' has already been redacted; redaction is irreversible.'
            );
        }

        DB::transaction(function () use ($redactedById, $reason): void {
            $this->payloadRecord()->firstOrFail()->update(['payload' => null]);

            $this->update([
                'redacted_at'     => now(),
                'redacted_by_id'  => $redactedById,
                'redacted_reason' => $reason,
            ]);
        });
    }

    protected static function newFactory(): PublicationVersionFactory
    {
        return PublicationVersionFactory::new();
    }
}
