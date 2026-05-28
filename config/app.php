<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\RouteServiceProvider;
use Astrotomic\Translatable\TranslatableServiceProvider;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Facades\Image;
use Intervention\Image\ImageServiceProvider;
use Konekt\Concord\ConcordServiceProvider;
use Konekt\Concord\Facades\Concord;
use Konekt\Concord\Facades\Helper;
use Maatwebsite\Excel\ExcelServiceProvider;
use Maatwebsite\Excel\Facades\Excel;
use OwenIt\Auditing\AuditingServiceProvider;
use Prettus\Repository\Providers\RepositoryServiceProvider;
use Webkul\Admin\Providers\AdminServiceProvider;
use Webkul\AdminApi\Providers\AdminApiServiceProvider;
use Webkul\Attribute\Providers\AttributeServiceProvider;
use Webkul\Category\Providers\CategoryServiceProvider;
use Webkul\Completeness\Providers\CompletenessServiceProvider;
use Webkul\Core\Facades\Core;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Core\Providers\EnvValidatorServiceProvider;
use Webkul\DataGrid\Providers\DataGridServiceProvider;
use Webkul\DataTransfer\Providers\DataTransferServiceProvider;
use Webkul\DebugBar\Providers\DebugBarServiceProvider;
use Webkul\ElasticSearch\Providers\ElasticSearchServiceProvider;
use Webkul\FPC\Providers\FPCServiceProvider;
use Webkul\HistoryControl\Providers\HistoryControlServiceProvider;
use Webkul\Installer\Providers\InstallerServiceProvider;
use Webkul\MagicAI\Providers\MagicAIServiceProvider;
use Webkul\Notification\Providers\NotificationServiceProvider;
use Webkul\Product\Facades\ProductImage;
use Webkul\Product\Facades\ProductVideo;
use Webkul\Product\Providers\ProductServiceProvider;
use Webkul\Theme\Providers\ThemeServiceProvider;
use Webkul\User\Providers\UserServiceProvider;
use Webkul\Webhook\Providers\WebhookServiceProvider;

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'UnoPim'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services your application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Admin URL
    |--------------------------------------------------------------------------
    |
    | This URL suffix is used to define the admin url for example
    | admin/ or backend/
    |
    */

    'admin_url' => env('APP_ADMIN_URL', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => env('APP_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Base Currency Code
    |--------------------------------------------------------------------------
    |
    | Here you may specify the base currency code for your application.
    |
    */

    'currency' => env('APP_CURRENCY', 'USD'),

    /*
    |--------------------------------------------------------------------------
    | Default channel Code
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default channel code for your application.
    |
    */

    'channel' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => ServiceProvider::defaultProviders()->merge([
        /**
         * Package service providers.
         */
        TranslatableServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        ImageServiceProvider::class,
        ConcordServiceProvider::class,
        ExcelServiceProvider::class,
        RepositoryServiceProvider::class,
        AuditingServiceProvider::class,

        /**
         * Application service providers.
         */
        AppServiceProvider::class,
        AuthServiceProvider::class,
        EventServiceProvider::class,
        RouteServiceProvider::class,

        /**
         * Webkul package service providers.
         */
        AdminApiServiceProvider::class,
        AdminServiceProvider::class,
        AttributeServiceProvider::class,
        CategoryServiceProvider::class,
        CoreServiceProvider::class,
        EnvValidatorServiceProvider::class,
        DataGridServiceProvider::class,
        DataTransferServiceProvider::class,
        DebugBarServiceProvider::class,
        FPCServiceProvider::class,
        HistoryControlServiceProvider::class,
        InstallerServiceProvider::class,
        MagicAIServiceProvider::class,
        NotificationServiceProvider::class,
        ProductServiceProvider::class,
        ThemeServiceProvider::class,
        UserServiceProvider::class,
        ElasticSearchServiceProvider::class,
        WebhookServiceProvider::class,
        CompletenessServiceProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Facade::defaultAliases()->merge([
        'Concord'      => Concord::class,
        'Core'         => Core::class,
        'Excel'        => Excel::class,
        'Helper'       => Helper::class,
        'Image'        => Image::class,
        'PDF'          => Pdf::class,
        'ProductImage' => ProductImage::class,
        'ProductVideo' => ProductVideo::class,
        'Redis'        => Redis::class,
    ])->toArray(),
];
