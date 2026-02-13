<?php

namespace Webkul\Completeness\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Completeness\Contracts\CompletenessSetting as CompletenessSettingContracts;
use Webkul\Completeness\Database\Factories\CompletenessSettingFactory;
use Webkul\Core\Models\Channel;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class CompletenessSetting extends Model implements CompletenessSettingContracts
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'family_id',
        'attribute_id',
        'channel_id',
        'tenant_id',
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

    /**
     * Create a new factory instance for the model
     */
    protected static function newFactory(): Factory
    {
        return CompletenessSettingFactory::new();
    }
}
