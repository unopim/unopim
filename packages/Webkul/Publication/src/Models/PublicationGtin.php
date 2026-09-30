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
 * and is append-only.
 */
#[Fillable(['publication_id', 'gtin', 'recorded_at'])]
#[Table(name: 'publication_gtins')]
class PublicationGtin extends Model implements PublicationGtinContract
{
    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $row): void {
            throw new ImmutableVersionException('GTIN history row '.$row->id.' is immutable.');
        });

        static::deleting(function (self $row): void {
            throw new ImmutableVersionException('GTIN history row '.$row->id.' cannot be deleted.');
        });
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }
}
