<?php

namespace Webkul\ChannelConnector\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webkul\ChannelConnector\Contracts\ChannelConnector as ChannelConnectorContract;
use Webkul\ChannelConnector\Presenters\ConnectorHistoryPresenter;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ChannelConnector extends Model implements ChannelConnectorContract, HistoryContract, PresentableHistoryInterface
{
    use BelongsToTenant, HistoryTrait;

    protected $table = 'channel_connectors';

    protected $historyTags = ['channel_connector'];

    protected $auditExclude = ['credentials'];

    protected $fillable = [
        'code',
        'name',
        'channel_type',
        'credentials',
        'settings',
        'sync_schedule',
        'status',
        'last_synced_at',
    ];

    protected $casts = [
        'credentials'    => 'encrypted:array',
        'settings'       => 'array',
        'sync_schedule'  => 'array',
        'last_synced_at' => 'datetime',
    ];

    public static function getPresenters(): array
    {
        return [
            'status'         => ConnectorHistoryPresenter::class,
            'channel_type'   => ConnectorHistoryPresenter::class,
            'settings'       => ConnectorHistoryPresenter::class,
            'name'           => ConnectorHistoryPresenter::class,
            'code'           => ConnectorHistoryPresenter::class,
            'sync_schedule'  => ConnectorHistoryPresenter::class,
            'last_synced_at' => ConnectorHistoryPresenter::class,
        ];
    }

    public function fieldMappings(): HasMany
    {
        return $this->hasMany(ChannelFieldMapping::class, 'channel_connector_id');
    }

    public function syncJobs(): HasMany
    {
        return $this->hasMany(ChannelSyncJob::class, 'channel_connector_id');
    }

    public function productMappings(): HasMany
    {
        return $this->hasMany(ProductChannelMapping::class, 'channel_connector_id');
    }
}
