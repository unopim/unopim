<?php

namespace Webkul\Publication\Models;

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
use Webkul\Publication\Database\Factories\PublicationFactory;
use Webkul\Publication\Enums\PublicationStatus;

#[Table(name: 'publications')]
class Publication extends Model implements HistoryContract, PublicationContract
{
    use HasFactory;
    use HistoryTrait;

    protected $guarded = [];

    protected $historyTags = ['publication'];

    protected $historyFields = ['status'];

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

    public function currentVersion(int $localeId): ?PublicationVersion
    {
        return $this->versions()
            ->where('locale_id', $localeId)
            ->where('is_current', true)
            ->first();
    }

    protected static function newFactory(): PublicationFactory
    {
        return PublicationFactory::new();
    }
}
