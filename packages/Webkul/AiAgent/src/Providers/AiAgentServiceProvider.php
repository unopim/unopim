<?php

namespace Webkul\AiAgent\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools;
use Webkul\AiAgent\Console\Commands\CatalogQualityMonitor;
use Webkul\AiAgent\Console\Commands\CleanupTempFiles;
use Webkul\AiAgent\Contracts\AgentServiceContract;
use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\Listeners\ProductEventListener;
use Webkul\AiAgent\Services\AgentService;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\AiAgent\Services\ImageToProductService;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\AiAgent\Services\PromptBuilder;
use Webkul\AiAgent\Services\VisionService;

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

        // Inject assets into admin head — event name ends with .before
        Event::listen('unopim.admin.layout.head.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('ai-agent::layouts.head');
        });

        // Inject the global AI chat widget only when Agentic PIM is enabled
        Event::listen('unopim.admin.layout.content.after', function ($viewRenderEventManager) {
            if (auth()->guard('admin')->check() && core()->getConfigData('general.magic_ai.agentic_pim.enabled')) {
                $viewRenderEventManager->addTemplate('ai-agent::components.chat-widget');
            }
        });

        // Auto-enrichment: listen for product creation events
        Event::listen('catalog.product.create.after', [ProductEventListener::class, 'afterCreate']);

        // Register console commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                CatalogQualityMonitor::class,
                CleanupTempFiles::class,
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

        // Singletons: reuse the shared AiApiClient within a request lifecycle.
        $this->app->singleton(VisionService::class);
        $this->app->singleton(EnrichmentService::class);
        $this->app->singleton(ProductWriterService::class);
        $this->app->singleton(ImageToProductService::class);

        // Agent tool calling infrastructure
        $this->app->singleton(ToolRegistry::class, function ($app) {
            $registry = new ToolRegistry;

            // Product tools
            $registry->register($app->make(Tools\SearchProducts::class));
            $registry->register($app->make(Tools\FindSimilarProducts::class));
            $registry->register($app->make(Tools\GetProductDetails::class));
            $registry->register($app->make(Tools\CreateProduct::class));
            $registry->register($app->make(Tools\UpdateProduct::class));
            $registry->register($app->make(Tools\DeleteProducts::class));
            $registry->register($app->make(Tools\AttachImage::class));

            // Category tools
            $registry->register($app->make(Tools\ListCategories::class));
            $registry->register($app->make(Tools\AssignCategories::class));
            $registry->register($app->make(Tools\CreateCategory::class));
            $registry->register($app->make(Tools\UpdateCategory::class));
            $registry->register($app->make(Tools\CategoryTree::class));

            // Attribute tools
            $registry->register($app->make(Tools\ListAttributes::class));
            $registry->register($app->make(Tools\CreateAttribute::class));
            $registry->register($app->make(Tools\ManageOptions::class));

            // Family & group tools
            $registry->register($app->make(Tools\ManageFamilies::class));

            // AI/Vision/Image tools
            $registry->register($app->make(Tools\AnalyzeImage::class));
            $registry->register($app->make(Tools\GenerateContent::class));
            $registry->register($app->make(Tools\GenerateImage::class));
            $registry->register($app->make(Tools\EditImage::class));

            // Association tools
            $registry->register($app->make(Tools\ManageAssociations::class));

            // Export, import & bulk tools
            $registry->register($app->make(Tools\ExportProducts::class));
            $registry->register($app->make(Tools\ImportProducts::class));
            $registry->register($app->make(Tools\BulkEdit::class));

            // Admin tools
            $registry->register($app->make(Tools\ManageUsers::class));
            $registry->register($app->make(Tools\ManageRoles::class));
            $registry->register($app->make(Tools\ManageChannels::class));

            // Reporting & quality
            $registry->register($app->make(Tools\CatalogSummary::class));
            $registry->register($app->make(Tools\DataQualityReport::class));
            $registry->register($app->make(Tools\VerifyProduct::class));

            // Memory tools
            $registry->register($app->make(Tools\RememberFact::class));
            $registry->register($app->make(Tools\RecallMemory::class));

            // Planning
            $registry->register($app->make(Tools\PlanTasks::class));

            // Content feedback
            $registry->register($app->make(Tools\RateContent::class));

            return $registry;
        });

        $this->app->singleton(AgentRunner::class);
    }
}
