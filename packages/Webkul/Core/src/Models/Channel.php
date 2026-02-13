<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Webkul\Category\Models\CategoryProxy;
use Webkul\Core\Contracts\Channel as ChannelContract;
use Webkul\Core\Database\Factories\ChannelFactory;
use Webkul\Core\Eloquent\TranslatableModel;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class Channel extends TranslatableModel implements ChannelContract, HistoryContract
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    protected $table = 'channels';

    /** Tags for History */
    protected $historyTags = ['channel'];

    /** Fields for History */
    protected $historyFields = [
        'root_category_id',
    ];

    /** Proxy Table Fields for History */
    protected $historyProxyFields = [
        'currencies',
        'locales',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'root_category_id',
    ];

    protected $guarded = [];

    /**
     * Castable.
     *
     * @var array
     */
    protected $casts = [
    ];

    /**
     * Translated attributes.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name',
    ];

    /**
     * Get the channel locales.
     */
    public function locales(): BelongsToMany
    {
        return $this->belongsToMany(LocaleProxy::modelClass(), 'channel_locales', 'channel_id');
    }

    /**
     * Get the channel locales.
     */
    public function currencies(): BelongsToMany
    {
        return $this->belongsToMany(CurrencyProxy::modelClass(), 'channel_currencies', 'channel_id');
    }

    /**
     * Get the root category.
     */
    public function root_category(): BelongsTo
    {
        return $this->belongsTo(CategoryProxy::modelClass(), 'root_category_id');
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return ChannelFactory::new();
    }
}
