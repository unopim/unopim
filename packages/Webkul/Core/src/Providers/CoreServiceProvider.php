<?php

namespace Webkul\Core\Providers;

use Elastic\Elasticsearch\Client as ElasticSearchClient;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\ImageManager;
use Webkul\Core\CatalogScope;
use Webkul\Core\Console\Commands\TranslationsChecker;
use Webkul\Core\Console\Commands\UnoPimPublish;
use Webkul\Core\Console\Commands\UnoPimVersion;
use Webkul\Core\Contracts\Database\Grammar;
use Webkul\Core\Core;
use Webkul\Core\ElasticSearch;
use Webkul\Core\Exceptions\Handler;
use Webkul\Core\Facades\Core as CoreFacade;
use Webkul\Core\Facades\ElasticSearch as ElasticSearchFacade;
use Webkul\Core\Helpers\Database\GrammarQueryManager;
use Webkul\Core\Helpers\Locales as LocalesHelper;
use Webkul\Core\Http\Middleware\EnableDebugForAllowedIps;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\View\Compilers\BladeCompiler;
use Webkul\Theme\ViewRenderEventManager;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /*
         * Pin Laravel's url() / asset() / Vite root + scheme to APP_URL so
         * frontend asset URLs cannot be redirected to an attacker origin
         * by a poisoned Host / X-Forwarded-Host header.
         */
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);

            if ($scheme = parse_url((string) $appUrl, PHP_URL_SCHEME)) {
                URL::forceScheme($scheme);
            }
        }

        include __DIR__.'/../Http/helpers.php';

        $purifierCachePath = storage_path('app/purifier');

        if (! is_dir($purifierCachePath)) {
            mkdir($purifierCachePath, 0755, true);
        }

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->overrideMailConfiguration();

        $this->app['router']->pushMiddlewareToGroup('web', EnableDebugForAllowedIps::class);

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'core');

        $this->publishes([
            dirname(__DIR__).'/Config/concord.php'       => config_path('concord.php'),
            dirname(__DIR__).'/Config/media.php'         => config_path('media.php'),
            dirname(__DIR__).'/Config/repository.php'    => config_path('repository.php'),
            dirname(__DIR__).'/Config/visitor.php'       => config_path('visitor.php'),
            dirname(__DIR__).'/Config/elasticsearch.php' => config_path('elasticsearch.php'),
        ]);

        $this->app->register(EventServiceProvider::class);

        $this->app->register(VisitorServiceProvider::class);

        $this->app->bind(ExceptionHandler::class, Handler::class);

        /*
         * The framework's default redirect target for unauthenticated requests
         * is the (nonexistent) `login` route, which the Authenticate middleware
         * resolves eagerly while building the exception — throwing a 500 before
         * any handler runs. Point web requests at the admin login and let API
         * requests fall through to the JSON 401 in the exception handler.
         */
        Authenticate::redirectUsing(
            fn ($request) => $request->is('api/*') ? null : route('admin.session.create')
        );

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'core');

        Event::listen('unopim.shop.layout.body.after', static function (ViewRenderEventManager $viewRenderEventManager): void {
            $viewRenderEventManager->addTemplate('core::blade.tracer.style');
        });

        Event::listen('unopim.admin.layout.head', static function (ViewRenderEventManager $viewRenderEventManager): void {
            $viewRenderEventManager->addTemplate('core::blade.tracer.style');
        });

        /**
         * Image Cache route
         */
        if (is_string(config('imagecache.route'))) {
            $filenamePattern = '[ \w\\.\\/\\-\\@\(\)\=]+';

            /**
             * Route to access template applied image file
             */
            $this->app['router']->get(config('imagecache.route').'/{template}/{filename}', [
                'uses' => 'Webkul\Core\ImageCache\Controller@getResponse',
                'as'   => 'imagecache',
            ])->where(['filename' => $filenamePattern]);
        }

        DB::macro('rawQueryGrammar', fn (): Grammar => GrammarQueryManager::getGrammar());
    }

    /**
     * Register services.
     */
    /**
     * Override the mail transport with the values saved in the admin
     * Configuration (Email settings) when they are present, falling back to the
     * environment-driven mail config otherwise.
     */
    protected function overrideMailConfiguration(): void
    {
        try {
            if (! Schema::hasTable('core_config')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $prefix = 'emails.configure.email_settings.';

        $host = core()->getConfigData($prefix.'mail_host');

        if (! $host) {
            return;
        }

        $encryption = core()->getConfigData($prefix.'mail_encryption');

        config([
            'mail.mailers.smtp.host'       => $host,
            'mail.mailers.smtp.port'       => core()->getConfigData($prefix.'mail_port') ?: config('mail.mailers.smtp.port'),
            'mail.mailers.smtp.username'   => core()->getConfigData($prefix.'mail_username') ?: config('mail.mailers.smtp.username'),
            'mail.mailers.smtp.password'   => core()->getConfigData($prefix.'mail_password') ?: config('mail.mailers.smtp.password'),
            'mail.mailers.smtp.encryption' => ($encryption && $encryption !== 'none') ? $encryption : null,
        ]);

        if ($fromAddress = core()->getConfigData($prefix.'shop_email_from')) {
            config(['mail.from.address' => $fromAddress]);
        }

        if ($fromName = core()->getConfigData($prefix.'sender_name')) {
            config(['mail.from.name' => $fromName]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/media.php', 'media');

        $this->app->singleton('image_manager', function ($app): ImageManager {
            $driver = $app['config']->get('image.driver', 'gd');

            return match ($driver) {
                'imagick' => new ImageManager(new ImagickDriver),
                default   => new ImageManager(new GdDriver),
            };
        });

        $this->app->alias('image_manager', ImageManager::class);

        $this->registerFacades();

        $this->registerCommands();

        $this->registerBladeCompiler();
    }

    /**
     * Register Bouncer as a singleton.
     */
    protected function registerFacades(): void
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('core', CoreFacade::class);

        $this->app->singleton('core', fn () => app()->make(Core::class));

        /**
         * The request's catalog scope. Scoped, not a singleton: Octane keeps singletons alive across
         * requests inside a worker, which would leak one admin's locale into the next admin's page.
         */
        $this->app->scoped(CatalogScope::class, fn ($app): CatalogScope => new CatalogScope(
            $app->make(LocaleRepository::class),
            $app->make(ChannelRepository::class),
        ));

        /**
         * Astrotomic registers its own Locales helper as a singleton, but every TranslatableModel
         * resolves this subclass, which is otherwise rebuilt on each getLocalesHelper() call and
         * reloads all locales every time. Astrotomic hits that helper several times per translated
         * attribute, so serializing a page of models turned into thousands of locale reloads. Scope
         * it so it loads once per request — Octane-safe, since a locale added mid-worker shows next request.
         */
        $this->app->scoped(LocalesHelper::class);

        /**
         * Register ElasticSearch as a singleton.
         */
        $this->app->singleton('elasticsearch', fn (): ElasticSearch => new ElasticSearch);

        $loader->alias('elasticsearch', ElasticSearchFacade::class);

        $this->app->singleton(ElasticSearchClient::class, fn (): ElasticSearchClient => app()->make('elasticsearch')->connection());
    }

    /**
     * Register the console commands of this package.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                TranslationsChecker::class,
                UnoPimPublish::class,
                UnoPimVersion::class,
            ]);
        }
    }

    /**
     * Register the Blade compiler implementation.
     */
    public function registerBladeCompiler(): void
    {
        $this->app->singleton('blade.compiler', fn ($app): BladeCompiler => new BladeCompiler($app['files'], $app['config']['view.compiled']));
    }
}
