<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\ChannelConnector\Contracts\ChannelFieldMapping as ChannelFieldMappingContract;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelFieldMapping extends Model implements ChannelFieldMappingContract
{
    use BelongsToTenant;

    protected $table = 'channel_field_mappings';

    protected $fillable = [
        'channel_connector_id',
        'unopim_attribute_code',
        'channel_field',
        'direction',
        'transformation',
        'locale_mapping',
        'sort_order',
    ];

    protected $casts = [
        'transformation' => 'array',
        'locale_mapping' => 'array',
        'sort_order'     => 'integer',
    ];

    public function connector(): BelongsTo
    {
        return $this->belongsTo(ChannelConnector::class, 'channel_connector_id');
    }
}
