<?php

namespace Webkul\Publication\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\LocaleProxy;
use Webkul\Publication\Contracts\PublicationViewStat as PublicationViewStatContract;

/**
 * A GDPR-safe daily view counter: one row per (publication, locale, day) holding
 * a count only. No visitor identity, no raw IP, and no per-view row is ever stored.
 */
#[Fillable([
    'publication_id',
    'locale_id',
    'viewed_on',
    'views',
])]
#[Table(name: 'publication_view_stats')]
class PublicationViewStat extends Model implements PublicationViewStatContract
{
    protected function casts(): array
    {
        return [
            'viewed_on' => 'date',
            'views'     => 'integer',
        ];
    }

    public function publication(): BelongsTo
    {
        return $this->belongsTo(PublicationProxy::modelClass());
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(LocaleProxy::modelClass());
    }
}
