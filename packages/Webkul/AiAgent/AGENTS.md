# AI Agent Module — Agents Architecture

## Overview

The **Agents** subsystem provides reusable, injectable agent classes for common AI-driven product operations. Each agent encapsulates domain-specific logic (image analysis, description generation, categorization, etc.) while leveraging the underlying pipeline architecture.

---

## Architecture

```
Agent Layer
├── BaseAgent (abstract)
│   └── Provides common execute/executeAsync patterns
├── ImageProductAgent (concrete)
│   └── Analyzes product images → structured data
├── TextDescriptionAgent (concrete)
│   └── Generates marketing copy from specifications
├── ProductCategorizerAgent (concrete)
│   └── Auto-tags and categorizes products
└── BulkProductEnricherAgent (concrete)
    └── Batch-enriches product data

             ↓ (both inject)

AgentService (orchestrator)
├── Resolves pipeline stages
├── Executes via pipeline
├── Manages sync/async dispatch
└── Returns AgentResult

             ↓

Pipeline + Stages
└── Generic: Validate → BuildPrompt → CallAI → Parse → Log
```

---

## Core Concepts

### 1. **BaseAgent** (Abstract)

All concrete agents extend `BaseAgent` which provides:

- **`execute()`** — Synchronous execution (blocking, immediate response)
- **`executeAsync()`** — Asynchronous dispatch to queue
- Dependency injection of `AgentService`
- Abstract methods: `getDefaultSystemPrompt()`, `buildInstruction()`

```php
abstract class BaseAgent
{
    abstract protected function getDefaultSystemPrompt(): string;
    abstract protected function buildInstruction(mixed $input): string;

    public function execute(mixed $input, int $agentId, int $credentialId, array $context = []): AgentResult
    public function executeAsync(mixed $input, int $agentId, int $credentialId, array $context = []): void
}
```

### 2. **Concrete Agents**

Each agent specializes in one AI task:

#### ImageProductAgent

Analyzes product images and extracts:
- Product name, description, category
- Colors, materials, dimensions
- Price estimates, quality assessment
- Key features, use cases

```php
$agent = app(ImageProductAgent::class);
$result = $agent->analyze(
    imageSource: 'https://example.com/img.jpg',  // URL or file path
    agentId: 1,
    credentialId: 1,
);
```

#### TextDescriptionAgent

Generates marketing content:
- Optimized product names
- Short & long descriptions
- Key benefits, selling points
- SEO keywords, target audience

```php
$agent = app(TextDescriptionAgent::class);
$result = $agent->execute(
    input: ['title' => 'Nike Shoes', 'specs' => [...]],
    agentId: 2,
    credentialId: 1,
);
```

#### ProductCategorizerAgent

Assigns metadata:
- Primary & secondary categories
- Tags and attributes
- Confidence levels

```php
$agent = app(ProductCategorizerAgent::class);
$result = $agent->execute(
    input: ['name' => 'Blue Cotton Shirt'],
    agentId: 3,
    credentialId: 1,
);
```

#### BulkProductEnricherAgent

Processes multiple products:
- Batch enrichment API
- Quality scoring per product
- Missing field detection

```php
$agent = app(BulkProductEnricherAgent::class);
$result = $agent->enrichBatch(
    products: [...],
    agentId: 4,
    credentialId: 1,
);
```

---

## Dependency Injection

All agents are automatically injectable via Laravel's service container.

### In Controllers

```php
class ProductController extends Controller
{
    public function importImage(ImageProductAgent $agent)
    {
        $result = $agent->analyze($imageUrl, 1, 1);
        return response()->json($result->toArray());
    }
}
```

### In Services

```php
class ProductImportService
{
    public function __construct(
        protected ImageProductAgent $imageAgent,
        protected TextDescriptionAgent $descAgent,
    ) {}

    public function enrichProduct(string $imageUrl)
    {
        $imageData = $this->imageAgent->analyze($imageUrl, 1, 1);
        $descriptions = $this->descAgent->execute($imageData->data, 2, 1);
        // ... combine and persist
    }
}
```

### In Queue Jobs

```php
class ProcessImageJob implements ShouldQueue
{
    public function handle(ImageProductAgent $agent)
    {
        $agent->analyzeAsync($this->imageUrl, 1, 1);
    }
}
```

### In Console Commands

```php
class AnalyzeImageCommand extends Command
{
    public function handle(ImageProductAgent $agent)
    {
        $result = $agent->analyze($imageUrl, 1, 1);
        $this->info('Done: ' . $result->output);
    }
}
```

---

## Creating Custom Agents

### Step 1: Extend BaseAgent

```php
namespace Webkul\AiAgent\Agents;

class MyCustomAgent extends BaseAgent
{
    protected function getDefaultSystemPrompt(): string
    {
        return <<<'PROMPT'
            You are an expert in [DOMAIN].
            Return valid JSON with keys: [...].
            PROMPT;
    }

    protected function buildInstruction(mixed $input): string
    {
        return "Analyze: " . json_encode($input);
    }
}
```

### Step 2: Use It (Auto-Injected)

```php
// No registration needed! Laravel auto-resolves from type hint.
public function myMethod(MyCustomAgent $agent)
{
    $result = $agent->execute($input, 1, 1);
}
```

### Step 3: Call execute() or executeAsync()

```php
// Sync
$result = $agent->execute($input, agentId: 1, credentialId: 1);
if ($result->success) {
    $data = $result->data;  // Parsed JSON
}

// Async
$agent->executeAsync($input, agentId: 1, credentialId: 1);  // Returns void, queued
```

---

## Input/Output Examples

### ImageProductAgent

**Input**
```
imageSource: "https://example.com/shoes.jpg"
```

**Output (AgentResult)**
```json
{
  "success": true,
  "output": "[raw AI response]",
  "data": {
    "name": "Blue Nike Air Max",
    "description": "Premium running shoes...",
    "category": "Footwear",
    "colors": ["blue", "white", "gray"],
    "materials": ["mesh", "rubber", "nylon"],
    "quality": "premium"
  },
  "tokensUsed": 450,
  "metadata": {
    "agentName": "Image Analyzer",
    "executionTimeMs": 2100
  }
}
```

### TextDescriptionAgent

**Input**
```php
[
    'title' => 'Running Shoes',
    'specifications' => [
        'color' => 'blue',
        'material' => 'mesh',
        'brand' => 'Nike'
    ]
]
```

**Output**
```json
{
  "success": true,
  "data": {
    "productName": "Premium Blue Mesh Running Shoes",
    "shortDescription": "High-performance running shoes with premium mesh...",
    "longDescription": "...",
    "keyBenefits": ["breathability", "durability", "comfort"],
    "targetAudience": "Serious runners",
    "seoKeywords": ["running shoes", "mesh shoes", "blue athletic shoes"]
  }
}
```

---

## Context Passing

Additional context can be passed to agents for logging, filtering, or cross-stage lookup:

```php
$result = $agent->analyze(
    imageSource: $imageUrl,
    agentId: 1,
    credentialId: 1,
    additionalContext: [
        'userId'        => auth()->id(),
        'locale'        => 'en_US',
        'batchId'       => 'batch_123',
        'importSource'  => 'instagram',
        'customField'   => 'value',
    ],
);

// Available in pipeline stages as $payload->context['userId'], etc.
```

---

## Sync vs Async

| Mode | When to Use | Returns | Execution |
|------|-----------|---------|-----------|
| **Sync** (`execute`) | Immediate feedback needed (HTTP response, console output) | `AgentResult` | Blocking, right now |
| **Async** (`executeAsync`) | Background processing (batch imports, bulk enrichment) | `void` | Queued, background worker |

```php
// Interactive: block and return result immediately
$result = $agent->analyze($imageUrl, 1, 1);
return response()->json($result->toArray());

// Bulk: queue and return acceptance
$agent->analyzeAsync($imageUrl, 1, 1);
return response()->json(['status' => 'queued']);
```

---

## Integration Examples

### Product Import Workflow

```php
class ProductImporter
{
    public function __construct(
        protected ImageProductAgent $imageAgent,
        protected TextDescriptionAgent $descAgent,
        protected ProductCategorizerAgent $catAgent,
        protected ProductRepository $repo,
    ) {}

    public function importFromImage(string $imageUrl): Product
    {
        // 1. Analyze image
        $imageResult = $this->imageAgent->analyze($imageUrl, 1, 1);
        if (!$imageResult->success) throw new Exception(...);

        // 2. Generate description
        $descResult = $this->descAgent->execute($imageResult->data, 2, 1);

        // 3. Categorize
        $catResult = $this->catAgent->execute($imageResult->data, 3, 1);

        // 4. Persist
        return $this->repo->create([
            'name'         => $imageResult->data['name'],
            'description'  => $descResult->data['longDescription'],
            'category_id'  => $catResult->data['primaryCategory'],
            'tags'         => $catResult->data['tags'],
        ]);
    }
}
```

### Webhook Handler

```php
class ProductWebhookController
{
    public function handle(
        ImageProductAgent $agent,
        Request $request,
    ) {
        $imageUrl = $request->input('image_url');

        // Queue async analysis
        $agent->analyzeAsync(
            imageSource: $imageUrl,
            agentId: 1,
            credentialId: 1,
            additionalContext: [
                'webhookId' => $request->input('id'),
                'source'    => 'external_api',
            ],
        );

        return response()->json(['status' => 'processing']);
    }
}
```

---

## Extension Points

### 1. Custom Image Preprocessing

Override `prepareImageContent()`:

```php
class CustomImageAgent extends ImageProductAgent
{
    protected function prepareImageContent(string $imageSource): string
    {
        // Add watermark detection, crop, resize, etc.
        return parent::prepareImageContent($imageSource);
    }
}
```

### 2. Custom Prompt Building

Override `buildInstruction()`:

```php
class LocalizedProductAgent extends TextDescriptionAgent
{
    protected function buildInstruction(mixed $input): string
    {
        $locale = app()->getLocale();
        return "Generate descriptions in $locale for: " . json_encode($input);
    }
}
```

### 3. Custom Pipeline Stages

Add specialized stages in agent config:

```php
Agent::create([
    'name'     => 'Advanced Image Analyzer',
    'pipeline' => [
        ValidateInputStage::class,
        CustomNsfwDetectionStage::class,  // Custom
        CustomImageEnhanceStage::class,   // Custom
        BuildPromptStage::class,
        CallAiProviderStage::class,
        ParseResponseStage::class,
        LogExecutionStage::class,
    ],
]);
```

---

## Best Practices

1. **Always use dependency injection** — Never instantiate agents directly.
   ```php
   // ✅ Good
   public function __construct(ImageProductAgent $agent) {}

   // ❌ Bad
   $agent = new ImageProductAgent(...);
   ```

2. **Pass context for observability** — Include userId, locale, batch info.
   ```php
   $agent->analyze($imageUrl, 1, 1, context: ['userId' => auth()->id()]);
   ```

3. **Use async for batch operations** — Queued jobs for bulk processing.
   ```php
   foreach ($images as $image) {
       $agent->analyzeAsync($image, 1, 1);  // All queued, fast return
   }
   ```

4. **Handle failures gracefully** — Check `AgentResult::success`.
   ```php
   $result = $agent->analyze(...);
   if (!$result->success) {
       Log::error($result->error);
       return response()->json(['error' => $result->error], 400);
   }
   ```

5. **Extend agents, don't modify** — Create new subclasses for customization.
   ```php
   class MyAgentAgent extends ImageProductAgent { }
   ```

---

## Testing

### Mock Agents

```php
public function test_product_import()
{
    $mockAgent = Mockery::mock(ImageProductAgent::class);
    $mockAgent->shouldReceive('analyze')
        ->andReturn(AgentResult::success(
            output: 'test',
            data: ['name' => 'Test Product'],
        ));

    $this->app->instance(ImageProductAgent::class, $mockAgent);
    // ... test your code
}
```

---

## File Structure

```
src/Agents/
├── BaseAgent.php                    # Abstract base class
├── ImageProductAgent.php            # Image ↔ product data
├── TextDescriptionAgent.php         # Descriptions & copy
├── ProductCategorizerAgent.php      # Auto-categorization
└── BulkProductEnricherAgent.php     # Batch enrichment

src/Examples/
├── ExampleAgentController.php       # HTTP usage
├── AnalyzeProductImageCommand.php   # CLI usage
└── EnrichProductsBatchCommand.php   # Batch CLI usage

USAGE_EXAMPLES.md                    # Detailed examples
```

---

See [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) for 8 practical patterns and complete code samples.
