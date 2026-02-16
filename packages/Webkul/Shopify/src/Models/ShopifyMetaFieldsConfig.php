<?php

namespace Webkul\Shopify\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Shopify\Contracts\ShopifyMetaFieldsConfig as ShopifyMetaFieldsContract;
use Webkul\Shopify\Database\Factories\ShopifyMetaFieldFactory;
use Webkul\Shopify\Presenters\JsonDataPresenter;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ShopifyMetaFieldsConfig extends Model implements HistoryContract, PresentableHistoryInterface, ShopifyMetaFieldsContract
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    protected $table = 'wk_shopify_metafield_defination';

    protected $historyTags = ['shopify_meta_fields'];

    protected $fillable = [
        'ownerType',
        'code',
        'type',
        'attribute',
        'name_space_key',
        'description',
        'validations',
        'listvalue',
        'pin',
        'options',
        'storefronts',
        'ContentTypeName',
        'apiUrl',
    ];

    protected $casts = [
        'validations' => 'string',
        'options'     => 'string',
    ];

    /**
     * custom history presenters to be used while displaying the history for that column
     */
    public static function getPresenters(): array
    {
        return [
            'validations' => JsonDataPresenter::class,
            'options'     => JsonDataPresenter::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ShopifyMetaFieldFactory::new();
    }
}
