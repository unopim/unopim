<?php

use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

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
        Astrotomic\Translatable\TranslatableServiceProvider::class,
        Barryvdh\DomPDF\ServiceProvider::class,
        Intervention\Image\ImageServiceProvider::class,
        Konekt\Concord\ConcordServiceProvider::class,
        Maatwebsite\Excel\ExcelServiceProvider::class,
        Prettus\Repository\Providers\RepositoryServiceProvider::class,
        OwenIt\Auditing\AuditingServiceProvider::class,

        /**
         * Application service providers.
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        /**
         * Webkul package service providers.
         */
        Webkul\AdminApi\Providers\AdminApiServiceProvider::class,
        Webkul\Admin\Providers\AdminServiceProvider::class,
        Webkul\Attribute\Providers\AttributeServiceProvider::class,
        Webkul\Category\Providers\CategoryServiceProvider::class,
        Webkul\Core\Providers\CoreServiceProvider::class,
        Webkul\Core\Providers\EnvValidatorServiceProvider::class,
        Webkul\DataGrid\Providers\DataGridServiceProvider::class,
        Webkul\DataTransfer\Providers\DataTransferServiceProvider::class,
        Webkul\DebugBar\Providers\DebugBarServiceProvider::class,
        Webkul\FPC\Providers\FPCServiceProvider::class,
        Webkul\HistoryControl\Providers\HistoryControlServiceProvider::class,
        Webkul\Installer\Providers\InstallerServiceProvider::class,
        Webkul\MagicAI\Providers\MagicAIServiceProvider::class,
        Webkul\Notification\Providers\NotificationServiceProvider::class,
        Webkul\Product\Providers\ProductServiceProvider::class,
        Webkul\Theme\Providers\ThemeServiceProvider::class,
        Webkul\User\Providers\UserServiceProvider::class,
        Webkul\ElasticSearch\Providers\ElasticSearchServiceProvider::class,
        Webkul\Webhook\Providers\WebhookServiceProvider::class,
        Webkul\Completeness\Providers\CompletenessServiceProvider::class,
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
        'Concord'      => Konekt\Concord\Facades\Concord::class,
        'Core'         => Webkul\Core\Facades\Core::class,
        'Excel'        => Maatwebsite\Excel\Facades\Excel::class,
        'Helper'       => Konekt\Concord\Facades\Helper::class,
        'Image'        => Intervention\Image\Facades\Image::class,
        'PDF'          => Barryvdh\DomPDF\Facade\Pdf::class,
        'ProductImage' => Webkul\Product\Facades\ProductImage::class,
        'ProductVideo' => Webkul\Product\Facades\ProductVideo::class,
        'Redis'        => Illuminate\Support\Facades\Redis::class,
    ])->toArray(),
];
