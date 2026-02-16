<?php

namespace Webkul\Shopify\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Webkul\HistoryControl\Contracts\HistoryAuditable as HistoryContract;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Traits\HistoryTrait;
use Webkul\Shopify\Contracts\ShopifyCredentialsConfig as ShopifyCredentialsContract;
use Webkul\Shopify\Database\Factories\ShopifyCredentialFactory;
use Webkul\Shopify\Presenters\JsonDataPresenter;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

class ShopifyCredentialsConfig extends Model implements HistoryContract, PresentableHistoryInterface, ShopifyCredentialsContract
{
    use BelongsToTenant, HasFactory;
    use HistoryTrait;

    protected $table = 'wk_shopify_credentials_config';

    protected $historyTags = ['shopify_credentials'];

    protected $auditExclude = ['storeLocales', 'accessToken'];

    protected $fillable = [
        'shopUrl',
        'accessToken',
        'active',
        'apiVersion',
        'storelocaleMapping',
        'storeLocales',
        'defaultSet',
        'resources',
        'extras',
        'salesChannel',
    ];

    protected $casts = [
        'storelocaleMapping' => 'array',
        'storeLocales'       => 'array',
        'extras'             => 'array',
        'accessToken'        => 'encrypted',
    ];

    /**
     * custom history presenters to be used while displaying the history for that column
     */
    public static function getPresenters(): array
    {
        return [
            'storelocaleMapping' => JsonDataPresenter::class,
            'extras'             => JsonDataPresenter::class,
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return ShopifyCredentialFactory::new();
    }
}
