<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Publication\Contracts\PublicationGtin as PublicationGtinContract;
use Webkul\Publication\Exceptions\ImmutableVersionException;

/**
 * Every GTIN a publication has ever published under. `publications.gtin` is the current one;
 * a printed `/01/{gtin}` link must keep resolving after a correction, so the history is kept
 * and is append-only. The one permitted change is revoking a row, which retires a GTIN published by
 * mistake: the history fallback in `PublicationResolver::findByGtin()` skips it, and nothing un-revokes it.
 */
#[Fillable(['publication_id', 'gtin', 'recorded_at'])]
#[Table(name: 'publication_gtins')]
class PublicationGtin extends Model implements PublicationGtinContract
{
    protected function casts(): array
    {
        return ['recorded_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            $changed = array_diff(array_keys($row->getDirty()), ['revoked_at', 'updated_at']);

            if ($changed !== [] || $row->getOriginal('revoked_at') !== null) {
                throw new ImmutableVersionException('GTIN history row '.$row->id.' is immutable.');
            }
        });

        static::deleting(function (self $row): void {
            throw new ImmutableVersionException('GTIN history row '.$row->id.' cannot be deleted.');
        });
    }

    public function revoke(): bool
    {
        return $this->forceFill(['revoked_at' => now()])->save();
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }
}
