<?php

namespace Webkul\Core\Providers;

use Elastic\Elasticsearch\Client as ElasticSearchClient;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Mail\MailManager;
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
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Repositories\ChannelRepository;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\Core\RequestMemo;
use Webkul\Core\View\Compilers\BladeCompiler;
use Webkul\Theme\ViewRenderEventManager;

class CoreServiceProvider extends ServiceProvider
{
    const MAIL_CONFIGURED_KEY = 'mail.configured';

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Pin url()/asset()/Vite root + scheme to APP_URL so a poisoned Host header can't redirect asset URLs.
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

        $this->app->beforeResolving(MailManager::class, function (): void {
            $this->overrideMailConfiguration();
        });

        Event::listen('core.configuration.save.after', function (): void {
            app(RequestMemo::class)->forget(self::MAIL_CONFIGURED_KEY);
        });

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

        // Redirect unauthenticated web requests to admin login (default `login` route 500s); let API fall through to JSON 401.
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

        // Drop the request-scoped config memo on any config write so later reads in the same request see it.
        $forgetConfigMemo = fn () => app(RequestMemo::class)->forget('core_config.');
        CoreConfig::saved($forgetConfigMemo);
        CoreConfig::deleted($forgetConfigMemo);
    }

    /**
     * Override the mail transport with admin Configuration (Email settings) values
     * when present. Deferred until a mailer is resolved: reading the settings costs
     * a schema check and several config queries, and almost no request sends mail.
     *
     * The request is only marked as configured once the settings were actually
     * readable. A mailer resolved before the connection is usable would otherwise
     * claim the flag and leave the rest of the request on the unconfigured
     * transport, so the admin's SMTP host applied only on some requests.
     */
    protected function overrideMailConfiguration(): void
    {
        $memo = app(RequestMemo::class);

        if ($memo->has(self::MAIL_CONFIGURED_KEY)) {
            return;
        }

        try {
            if (! Schema::hasTable('core_config')) {
                return;
            }
        } catch (\Throwable) {
            return;
        }

        $memo->set(self::MAIL_CONFIGURED_KEY, true);

        $prefix = 'emails.configure.email_settings.';

        $host = core()->getConfigData($prefix.'mail_host');

        if (! $host) {
            return;
        }

        $encryption = core()->getConfigData($prefix.'mail_encryption');

        config([
            'mail.default'                 => 'smtp',
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

        // Scoped, not singleton: a singleton would leak one admin's catalog scope into the next request under Octane.
        $this->app->scoped(CatalogScope::class, fn ($app): CatalogScope => new CatalogScope(
            $app->make(LocaleRepository::class),
            $app->make(ChannelRepository::class),
        ));

        $this->app->scoped(RequestMemo::class);

        // Scope this Locales subclass so it loads once per request; Astrotomic otherwise rebuilds it per translated attribute.
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
