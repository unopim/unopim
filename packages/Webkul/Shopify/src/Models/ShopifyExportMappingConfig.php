<?php

namespace Webkul\Shopify\Models;

use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Shopify\Contracts\ShopifyExportMappingConfig as ShopifyExportMappingConfigContract;
use Webkul\Shopify\Presenters\JsonDataPresenter;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ShopifyExportMappingConfig extends Model implements HistoryContract, PresentableHistoryInterface, ShopifyExportMappingConfigContract
{
    use BelongsToTenant;
    use HistoryTrait;

    protected $table = 'shopify_setting_configuration_values';

    protected $historyTags = ['shopify_exportmapping'];

    protected $fillable = [
        'name',
        'mapping',
    ];

    protected $casts = [
        'mapping' => 'array',
    ];

    /**
     * custom history presenters to be used while displaying the history for that column
     */
    public static function getPresenters(): array
    {
        return [
            'mapping' => JsonDataPresenter::class,
        ];
    }
}
