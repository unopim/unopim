<?php

/**
 * EXAMPLE USAGE: ImageProductAgent
 *
 * The ImageProductAgent is fully reusable via dependency injection.
 * Here are practical usage patterns across different contexts.
 */

// ─────────────────────────────────────────────────────────────────
// 1. In a Controller (HTTP request handler)
// ─────────────────────────────────────────────────────────────────

use Webkul\AiAgent\Agents\ImageProductAgent;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductImportController extends Controller
{
    public function uploadAndAnalyze(
        ImageProductAgent $agent,  // Injected automatically
        Request $request,
    ) {
        $image = $request->file('image');
        $path  = $image->store('temp-analysis');

        // Execute synchronously
        $result = $agent->analyze(
            imageSource: storage_path('app/' . $path),
            agentId: 1,
            credentialId: 1,
        );

        if ($result->success) {
            // Create product from analyzed data
            \Webkul\Product\Models\Product::create($result->data);
        }

        return response()->json($result->toArray());
    }
}

// ─────────────────────────────────────────────────────────────────
// 2. In a Service Class (business logic)
// ─────────────────────────────────────────────────────────────────

use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\DTOs\AgentResult;

class ProductImportService
{
    public function __construct(
        protected ImageProductAgent $imageAgent,
        protected ProductRepository $productRepository,
    ) {}

    public function importFromImageUrl(string $imageUrl): AgentResult
    {
        return $this->imageAgent->analyze(
            imageSource: $imageUrl,
            agentId: 1,
            credentialId: 1,
        );
    }

    public function importAsyncFromImageUrl(string $imageUrl): void
    {
        $this->imageAgent->analyzeAsync(
            imageSource: $imageUrl,
            agentId: 1,
            credentialId: 1,
        );
    }
}

// ─────────────────────────────────────────────────────────────────
// 3. In a Queue Job (async processing)
// ─────────────────────────────────────────────────────────────────

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Webkul\AiAgent\Agents\ImageProductAgent;

class ProcessProductImageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $imageUrl,
        public int $agentId = 1,
        public int $credentialId = 1,
    ) {}

    public function handle(ImageProductAgent $agent)
    {
        $result = $agent->analyze(
            imageSource: $this->imageUrl,
            agentId: $this->agentId,
            credentialId: $this->credentialId,
        );

        if ($result->success) {
            event(new ProductAnalyzed($result));
        }
    }
}

// Usage:
// ProcessProductImageJob::dispatch('https://example.com/img.jpg');

// ─────────────────────────────────────────────────────────────────
// 4. In a Console Command
// ─────────────────────────────────────────────────────────────────

use Illuminate\Console\Command;
use Webkul\AiAgent\Agents\ImageProductAgent;

class AnalyzeProductCommand extends Command
{
    protected $signature = 'product:analyze {imageUrl} {--agent-id=1}';

    public function handle(ImageProductAgent $agent)
    {
        $result = $agent->analyze(
            imageSource: $this->argument('imageUrl'),
            agentId: (int) $this->option('agent-id'),
            credentialId: 1,
        );

        if ($result->success) {
            $this->info('Analysis complete!');
            $this->table(array_keys($result->data), [array_values($result->data)]);
        } else {
            $this->error('Analysis failed: ' . $result->error);
        }
    }
}

// ─────────────────────────────────────────────────────────────────
// 5. In an Event Listener
// ─────────────────────────────────────────────────────────────────

use Webkul\AiAgent\Agents\ImageProductAgent;

class ProductUploadedListener
{
    public function __construct(
        protected ImageProductAgent $agent,
    ) {}

    public function handle(ProductUploaded $event)
    {
        // Asynchronously analyze the product image
        $this->agent->analyzeAsync(
            imageSource: $event->product->getImageUrl(),
            agentId: $event->product->agentId,
            credentialId: $event->product->credentialId,
        );
    }
}

// ─────────────────────────────────────────────────────────────────
// 6. In a Factory/Builder Pattern (creating multiple agents)
// ─────────────────────────────────────────────────────────────────

use Container;
use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\Agents\TextDescriptionAgent;
use Webkul\AiAgent\Agents\ProductCategorizerAgent;

class ProductAIFactory
{
    public static function makeImageAnalyzer(): ImageProductAgent
    {
        return app(ImageProductAgent::class);
    }

    public static function makeDescriptionGenerator(): TextDescriptionAgent
    {
        return app(TextDescriptionAgent::class);
    }

    public static function makeCategorizer(): ProductCategorizerAgent
    {
        return app(ProductCategorizerAgent::class);
    }

    /**
     * Full product enrichment pipeline
     */
    public static function enrichProduct(string $imageUrl): void
    {
        $imageAgent = self::makeImageAnalyzer();
        $descAgent  = self::makeDescriptionGenerator();
        $catAgent   = self::makeCategorizer();

        // 1. Analyze image
        $imageAnalysis = $imageAgent->analyze($imageUrl, 1, 1);
        if (! $imageAnalysis->success) {
            return;
        }

        // 2. Generate description
        $descResult = $descAgent->execute(
            input: $imageAnalysis->data,
            agentId: 2,
            credentialId: 1,
        );

        // 3. Categorize
        $catResult = $catAgent->execute(
            input: array_merge($imageAnalysis->data, $descResult->data),
            agentId: 3,
            credentialId: 1,
        );

        // All results ready for persistence
    }
}

// ─────────────────────────────────────────────────────────────────
// 7. In a Test (mocking)
// ─────────────────────────────────────────────────────────────────

use PHPUnit\Framework\TestCase;
use Mockery as m;
use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\DTOs\AgentResult;

class ProductImportTest extends TestCase
{
    public function test_image_analysis_succeeds()
    {
        $mockAgent = m::mock(ImageProductAgent::class);
        $mockAgent->shouldReceive('analyze')
            ->with(m::any(), 1, 1)
            ->andReturn(AgentResult::success(
                output: 'Test output',
                data: ['name' => 'Test Product'],
            ));

        $result = $mockAgent->analyze('test.jpg', 1, 1);

        $this->assertTrue($result->success);
        $this->assertEquals('Test Product', $result->data['name']);
    }
}

// ─────────────────────────────────────────────────────────────────
// 8. Using with Service Container
// ─────────────────────────────────────────────────────────────────

// Register a singleton or binding in ServiceProvider:
$container->singleton(ImageProductAgent::class, function ($app) {
    return new ImageProductAgent(
        agentService: $app->make(\Webkul\AiAgent\Services\AgentService::class),
    );
});

// Or auto-resolve:
$agent = app(ImageProductAgent::class);

// ─────────────────────────────────────────────────────────────────
// SYNC vs ASYNC
// ─────────────────────────────────────────────────────────────────

// Synchronous (blocking) — use for immediate feedback
$result = $agent->analyze($imageUrl, 1, 1);

// Asynchronous (queued) — use for bulk processing
$agent->analyzeAsync($imageUrl, 1, 1);  // Returns void, runs in background

// ─────────────────────────────────────────────────────────────────
// CONTEXT PASSING
// ─────────────────────────────────────────────────────────────────

$result = $agent->analyze(
    imageSource: $imageUrl,
    agentId: 1,
    credentialId: 1,
    additionalContext: [
        'userId'     => auth()->id(),
        'locale'     => app()->getLocale(),
        'storeId'    => 42,
        'importedAt' => now(),
    ],
);

// These are available in the AgentPayload.context and can be
// used by any pipeline stage for logging, filtering, etc.
