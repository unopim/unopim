<?php

namespace Webkul\AiAgent\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Console\Commands\CatalogQualityMonitor;
use Webkul\AiAgent\Console\Commands\CleanupTempFiles;
use Webkul\AiAgent\Console\Commands\IndexProductEmbeddings;
use Webkul\AiAgent\Contracts\AgentServiceContract;
use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\Listeners\ProductEventListener;
use Webkul\AiAgent\Observers\ProductEmbeddingObserver;
use Webkul\AiAgent\Services\AgentService;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\AiAgent\Services\ImageToProductService;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\AiAgent\Services\PromptBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingDocumentBuilder;
use Webkul\AiAgent\Services\VectorStore\ProductEmbeddingIndex;
use Webkul\AiAgent\Services\VisionService;
use Webkul\Product\Models\Product as Products;

class AiAgentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Routes — Route::middleware('web')->group() — NOT loadRoutesFrom()
        Route::middleware('web')->group(
            __DIR__.'/../../Routes/ai-agent-routes.php'
        );

        // Views
        $this->loadViewsFrom(__DIR__.'/../../Resources/views', 'ai-agent');

        // Translations
        $this->loadTranslationsFrom(__DIR__.'/../../Resources/lang', 'ai-agent');

        // Migrations — Database/Migration/ (singular)
        $this->loadMigrationsFrom(__DIR__.'/../../Database/Migration');

        // Inject the global AI chat widget only when Agentic PIM is enabled
        Event::listen('unopim.admin.layout.content.after', function ($viewRenderEventManager) {
            if (auth()->guard('admin')->check() && core()->getConfigData('general.magic_ai.agentic_pim.enabled')) {
                $viewRenderEventManager->addTemplate('ai-agent::components.chat-widget');
            }
        });

        // Auto-enrichment: listen for product creation events
        Event::listen('catalog.product.create.after', [ProductEventListener::class, 'afterCreate']);

        // Vector store: keep product embeddings in sync (gated inside the observer)
        Products::observe(ProductEmbeddingObserver::class);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CatalogQualityMonitor::class,
                CleanupTempFiles::class,
                IndexProductEmbeddings::class,
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(ModuleServiceProvider::class);

        $this->registerConfig();
        $this->registerBindings();
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../Config/ai-agent.php', 'ai-agent');
        $this->mergeConfigFrom(__DIR__.'/../../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__.'/../../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__.'/../../Config/exporters.php', 'exporters');
        $this->mergeConfigFrom(__DIR__.'/../../Config/quick_exporters.php', 'quick_exporters');
        $this->mergeConfigFrom(__DIR__.'/../../Config/importers.php', 'importers');
    }

    /**
     * Register contract bindings.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(AgentServiceContract::class, AgentService::class);
        $this->app->bind(PromptBuilderContract::class, PromptBuilder::class);

        $this->app->scoped(VisionService::class);
        $this->app->scoped(EnrichmentService::class);
        $this->app->scoped(ProductWriterService::class);
        $this->app->scoped(ImageToProductService::class);

        // Vector store services (stateless, safe as singletons under Octane)
        $this->app->singleton(ProductEmbeddingIndex::class);
        $this->app->singleton(ProductEmbeddingDocumentBuilder::class);

        // Agent tool calling infrastructure — populated from the ai-agent.tools
        // config map so packages can add/override/disable tools declaratively.
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry;

            foreach ($app['config']->get('ai-agent.tools', []) as $class => $metadata) {
                if (! ($metadata['enabled'] ?? true)) {
                    continue;
                }

                $registry->register($app->make($class), $metadata);
            }

            return $registry;
        });

        $this->app->singleton(AgentRunner::class);
    }
}
