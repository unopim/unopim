<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Publication\Contracts\PublicationCarrierIssuance as PublicationCarrierIssuanceContract;
use Webkul\Publication\Database\Factories\PublicationCarrierIssuanceFactory;
use Webkul\Publication\Exceptions\ImmutableVersionException;
use Webkul\User\Models\AdminProxy;

/**
 * One printed carrier: which release it was issued against, the exact string it
 * encodes, when and by whom. The row, not the image, is the durable answer to
 * "which QR code is in the manual and what did it point at", so it is immutable.
 */
#[Fillable([
    'publication_id',
    'release_id',
    'target',
    'format',
    'issued_at',
    'issued_by_id',
])]
#[Table(name: 'publication_carrier_issuances')]
class PublicationCarrierIssuance extends Model implements PublicationCarrierIssuanceContract
{
    use HasFactory;

    protected function casts(): array
    {
        return ['issued_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(function (self $issuance): void {
            $touched = array_diff(array_keys($issuance->getDirty()), ['updated_at']);

            if ($touched !== []) {
                throw new ImmutableVersionException(
                    'Carrier issuance '.$issuance->id.' is immutable; attempted to change: '.implode(', ', $touched)
                );
            }
        });

        static::deleting(function (self $issuance): void {
            throw new ImmutableVersionException('Carrier issuance '.$issuance->id.' cannot be deleted.');
        });
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }

    public function release(): BelongsTo
    {
        return $this->belongsTo(PublicationReleaseProxy::modelClass(), 'release_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'issued_by_id');
    }

    protected static function newFactory(): PublicationCarrierIssuanceFactory
    {
        return PublicationCarrierIssuanceFactory::new();
    }
}
