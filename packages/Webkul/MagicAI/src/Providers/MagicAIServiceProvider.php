<?php

namespace Webkul\MagicAI\Providers;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;
use Laravel\Ai\Providers\OpenAiProvider;
use Webkul\MagicAI\Facades\MagicAI as MagicAIFacade;
use Webkul\MagicAI\Gateways\OpenAiImageGateway;
use Webkul\MagicAI\MagicAI;

class MagicAIServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        include __DIR__.'/../Http/helpers.php';

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->extendOpenAiImageGateway();
    }

    protected function extendOpenAiImageGateway(): void
    {
        $this->app->make(AiManager::class)->extend(
            'openai',
            fn ($app, array $config): OpenAiProvider => new OpenAiProvider(
                new OpenAiImageGateway($app['events']),
                $config,
                $app->make(Dispatcher::class),
            ),
        );
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $loader = AliasLoader::getInstance();

        $loader->alias('magic_ai', MagicAIFacade::class);

        $this->app->singleton('magic_ai', fn (): MagicAI => new MagicAI);

        $this->registerConfig();
    }

    /**
     * Register configuration.
     */
    public function registerConfig(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/default_prompts.php', 'default_prompts');
    }
}
