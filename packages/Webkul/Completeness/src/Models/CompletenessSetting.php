<?php

namespace Webkul\Completeness\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Contracts\CompletenessSetting as CompletenessSettingContracts;
use Webkul\Completeness\Database\Factories\CompletenessSettingFactory;
use Webkul\Core\Models\Channel;

#[Fillable([
    'family_id',
    'attribute_id',
    'channel_id',
])]
class CompletenessSetting extends Model implements CompletenessSettingContracts
{
    use HasFactory;

    /**
     * Get the attribute family associated with this setting.
     *
     * @return BelongsTo<AttributeFamily, $this>
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamily::class, 'family_id');
    }

    /**
     * Get the attribute associated with this setting.
     *
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * Get the channel associated with this setting.
     *
     * @return BelongsTo<Channel, $this>
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return CompletenessSettingFactory::new();
    }
}
