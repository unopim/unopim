<?php

namespace Webkul\MagicAI\Providers;

use Closure;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;
use Laravel\Ai\Providers\OpenAiProvider;
use Psr\Http\Message\RequestInterface;
use Webkul\MagicAI\Facades\MagicAI as MagicAIFacade;
use Webkul\MagicAI\Gateways\OpenAiImageGateway;
use Webkul\MagicAI\MagicAI;
use Webkul\MagicAI\Services\ProviderCallGuard;

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

        $this->guardProviderCalls();
    }

    /**
     * Route every Http client request through the provider call guard, which
     * passes requests through untouched unless a provider call is in scope.
     */
    protected function guardProviderCalls(): void
    {
        Http::globalMiddleware(
            fn (callable $handler): Closure => fn (RequestInterface $request, array $options): PromiseInterface => app(ProviderCallGuard::class)->handle($handler, $request, $options),
        );
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

        $this->app->scoped(ProviderCallGuard::class);

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
