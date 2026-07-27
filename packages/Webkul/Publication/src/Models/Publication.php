<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\Core\Models\ChannelProxy;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Contracts\Publication as PublicationContract;
use Webkul\Publication\Contracts\PublicationVersion as PublicationVersionContract;
use Webkul\Publication\Database\Factories\PublicationFactory;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\ImmutableVersionException;

#[Fillable([
    'uuid',
    'product_id',
    'channel_id',
    'type',
    'status',
])]
#[Table(name: 'publications')]
class Publication extends Model implements HistoryContract, PublicationContract
{
    use HasFactory;
    use HistoryTrait;

    protected $historyTags = ['publication'];

    /**
     * `auditInclude` is the actual whitelist property (unlike the unwired
     * `$historyFields`) — re-pointing a passport at a different product must
     * leave a trail.
     */
    protected $auditInclude = ['status', 'uuid', 'product_id'];

    protected static function booted(): void
    {
        static::deleting(function (self $publication): void {
            if ($publication->versions()->exists()) {
                throw new ImmutableVersionException(
                    'Publication '.$publication->id.' has attested versions and cannot be deleted.'
                );
            }
        });
    }

    protected function casts(): array
    {
        return ['status' => PublicationStatus::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PublicationVersionProxy::modelClass());
    }

    /**
     * `orderByDesc('version')` guards against a data anomaly, not the normal
     * path: `is_current` is uniquely constrained to one row per locale, so at
     * most one row should ever match.
     */
    public function currentVersion(int $localeId): ?PublicationVersionContract
    {
        return $this->versions()
            ->where('locale_id', $localeId)
            ->where('is_current', true)
            ->orderByDesc('version')
            ->first();
    }

    protected static function newFactory(): PublicationFactory
    {
        return PublicationFactory::new();
    }
}
