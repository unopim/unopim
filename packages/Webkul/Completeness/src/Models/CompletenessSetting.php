<?php

namespace Webkul\Completeness\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Contracts\CompletenessSetting as CompletenessSettingContracts;
use Webkul\Core\Models\Channel;

class CompletenessSetting extends Model implements CompletenessSettingContracts
{
    protected $fillable = [
        'family_id',
        'attribute_id',
        'channel_id',
    ];

    /**
     * Get the attribute family associated with this setting.
     */
    public function family(): BelongsTo
    {
        return $this->belongsTo(AttributeFamily::class, 'family_id');
    }

    /**
     * Get the attribute associated with this setting.
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }

    /**
     * Get the channel associated with this setting.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }
}
