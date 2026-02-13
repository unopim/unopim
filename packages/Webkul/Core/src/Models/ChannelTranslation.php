<?php

namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\ChannelTranslation as ChannelTranslationContract;
use Webkul\Core\Database\Factories\ChannelTranslationFactory;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelTranslation extends Model implements ChannelTranslationContract, HistoryContract
{
    use BelongsToTenant;
    use HasFactory;
    use HistoryTrait;

    protected $historyTags = ['channel'];

    /**
     * Key => Label for history
     */
    protected $historyTranslatableFields = [
        'name' => 'Name',
    ];

    /**
     * Guarded.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Castable.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ChannelTranslationFactory::new();
    }

    /**
     * Id used for creating version for history
     *
     * {@inheritdoc}
     */
    public function getPrimaryModelIdForHistory(): int
    {
        return $this->channel_id;
    }
}
