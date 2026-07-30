<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\ChannelProxy;
use Webkul\Product\Models\ProductProxy;
use Webkul\Publication\Contracts\PublicationPublishAttempt as PublicationPublishAttemptContract;
use Webkul\Publication\Enums\PublishAttemptStatus;

/**
 * A queued publish an admin is waiting on, so the panel can report what the
 * worker did instead of leaving the request stuck on "queued" until a reload.
 */
#[Fillable([
    'product_id',
    'channel_id',
    'type',
    'locale_ids',
    'status',
    'outcome',
    'requested_by_id',
])]
#[Table(name: 'publication_publish_attempts')]
class PublicationPublishAttempt extends Model implements PublicationPublishAttemptContract
{
    protected function casts(): array
    {
        return [
            'locale_ids' => 'array',
            'outcome'    => 'array',
            'status'     => PublishAttemptStatus::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass());
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(ChannelProxy::modelClass());
    }

    /**
     * @return list<int>
     */
    public function publishedLocaleIds(): array
    {
        return array_map(intval(...), $this->outcome['published'] ?? []);
    }

    public function wasRefused(): bool
    {
        return (bool) ($this->outcome['refused'] ?? false);
    }
}
