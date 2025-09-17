<?php

namespace Webkul\Completeness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\ProductProxy;

class ProductCompletenessScore extends Model
{
    protected $table = 'product_completeness';

    protected $fillable = [
        'product_id',
        'channel_id',
        'locale_id',
        'score',
        'missing_count',
    ];

    /**
     * Get the product related to this score.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductProxy::modelClass(), 'product_id');
    }

    /**
     * Get the channel related to this score.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function locale(): BelongsTo
    {
        return $this->belongsTo(Locale::class, 'locale_id');
    }
}
