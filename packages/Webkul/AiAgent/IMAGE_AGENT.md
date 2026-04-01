# ImageProductAgent — Quick Reference

## Overview

`ImageProductAgent` analyzes product images and extracts structured data for product catalog enrichment.

- **Input**: Image URL, file path, or base64 data
- **Output**: Structured product data (name, category, colors, materials, quality, etc.)
- **Execution**: Sync (blocking) or Async (queued)
- **Injection**: Full constructor dependency injection

---

## Quick Start

### 1. Inject into Controller

```php
use Webkul\AiAgent\Agents\ImageProductAgent;

class ProductController extends Controller
{
    public function upload(ImageProductAgent $agent)
    {
        $result = $agent->analyze(
            imageSource: 'https://example.com/product.jpg',
            agentId: 1,
            credentialId: 1,
        );

        if ($result->success) {
            return response()->json($result->data);
        }

        return response()->json($result->error, 400);
    }
}
```

### 2. Image Sources Supported

```php
// URL
$agent->analyze('https://example.com/image.jpg', 1, 1);

// Local file path
$agent->analyze(storage_path('app/imports/image.jpg'), 1, 1);

// Base64 data URI
$agent->analyze(
    'data:image/jpeg;base64,/9j/4AAQSkZJRg...',
    1,
    1
);
```

### 3. Sync vs Async

```php
// **Synchronous** — blocks until done, returns result immediately
$result = $agent->analyze($imageUrl, 1, 1);
echo $result->data['name'];  // Available now

// **Asynchronous** — queued, returns void, processed in background
$agent->analyzeAsync($imageUrl, 1, 1);  // Fire and forget
// Check execution logs later via AgentExecution model
```

---

## API Reference

### analyze()

```php
public function analyze(
    string $imageSource,           // Required: URL, file path, or base64
    int $agentId,                  // Required: agent config ID
    int $credentialId,             // Required: AI credential ID
    array $additionalContext = [], // Optional: extra metadata
): AgentResult
```

**Returns**: `AgentResult` object with:
- `success: bool` — execution succeeded
- `output: string` — raw AI response
- `data: array` — parsed JSON structure
- `tokensUsed: int` — API tokens consumed
- `metadata: array` — pipeline execution info
- `error: ?string` — error message if failed

**Example**:
```php
$result = $agent->analyze($imageUrl, 1, 1);

if ($result->success) {
    $name = $result->data['name'];
    $colors = $result->data['colors'];
    $tokens = $result->tokensUsed;
}
```

### analyzeAsync()

```php
public function analyzeAsync(
    string $imageSource,
    int $agentId,
    int $credentialId,
    array $additionalContext = [],
): void
```

**Returns**: `void` (nothing immediately)

**Execution**: Queued via `ExecuteAgentJob` — processed by queue worker

**Example**:
```php
// Dispatch to queue
$agent->analyzeAsync($imageUrl, 1, 1);

// Returns immediately, execution happens later
return response()->json(['status' => 'queued']);

// Monitor via:
// AgentExecution::where('agentId', 1)->where('status', 'completed')->get()
```

---

## Returned Data Structure

```json
{
  "success": true,
  "output": "[full AI text response]",
  "data": {
    "name": "Blue Cotton T-Shirt",
    "description": "Premium quality cotton T-shirt with...",
    "category": "Apparel > Shirts > Casual",
    "price": 29.99,
    "colors": ["blue", "white"],
    "materials": ["100% cotton"],
    "estimatedSize": "M",
    "quality": "standard",
    "recommendedUses": ["casual wear", "summer"],
    "keyFeatures": ["breathable", "durable", "comfortable"]
  },
  "tokensUsed": 456,
  "metadata": {
    "agentName": "Image Analyzer",
    "executionTimeMs": 2100,
    "imageSource": "https://example.com/shirt.jpg",
    "imageType": "url"
  }
}
```

---

## Context Passing

Pass additional metadata through the pipeline:

```php
$result = $agent->analyze(
    imageSource: $imageUrl,
    agentId: 1,
    credentialId: 1,
    additionalContext: [
        'userId'       => auth()->id(),
        'locale'       => 'en_US',
        'importBatch'  => 'batch_12345',
        'source'       => 'instagram_scraper',
        'retailer'     => 'nike',
        'priceRange'   => 'premium',
    ],
);

// Available in pipeline stages for:
// - Filtering by user/locale
// - Logging/tracing batch operations
// - Contextual AI prompting
// - Audit trails
```

---

## Error Handling

```php
try {
    $result = $agent->analyze($imageUrl, 1, 1);

    if (!$result->success) {
        Log::warning('Image analysis failed', [
            'error' => $result->error,
            'imageUrl' => $imageUrl,
        ]);

        return response()->json(
            ['error' => 'Could not analyze image'],
            400,
        );
    }

    // Use result.data safely
    $product = Product::create($result->data);

} catch (InvalidArgumentException $e) {
    // Image URL/path validation failed
    return response()->json(['error' => $e->getMessage()], 400);

} catch (Exception $e) {
    // Other errors (AI API down, network, etc.)
    Log::error('Agent execution failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Server error'], 500);
}
```

---

## Common Patterns

### Bulk Image Import

```php
// Queue multiple images for async analysis
foreach ($productImages as $imageUrl) {
    $agent->analyzeAsync($imageUrl, 1, 1, context: [
        'batchId' => $batchId,
        'productId' => $productId,
    ]);
}

// Check results later:
$results = AgentExecution::where('status', 'completed')
    ->where('extras->batchId', $batchId)
    ->get();
```

### Product Enrichment Pipeline

```php
// Analyze image → generate description → save product
$imageResult = $agent->analyze($imageUrl, 1, 1);

if (!$imageResult->success) abort(400);

$descAgent = app(TextDescriptionAgent::class);
$descResult = $descAgent->execute($imageResult->data, 2, 1);

Product::create([
    'name'        => $imageResult->data['name'],
    'category'    => $imageResult->data['category'],
    'description' => $descResult->data['longDescription'],
    'colors'      => $imageResult->data['colors'],
    'materials'   => $imageResult->data['materials'],
    'image_url'   => $imageUrl,
]);
```

### Real-time Image Upload Handler

```php
public function uploadAndAnalyze(Request $request, ImageProductAgent $agent)
{
    $image = $request->file('image');
    $path = $image->store('products');

    $result = $agent->analyze(
        imageSource: storage_path('app/' . $path),
        agentId: 1,
        credentialId: 1,
    );

    return response()->json([
        'success' => $result->success,
        'product' => $result->data,
        'tokens'  => $result->tokensUsed,
    ]);
}
```

---

## Performance Tips

1. **Use async for bulk operations**
   ```php
   // Fast — returns immediately
   $agent->analyzeAsync($imageUrl, 1, 1);
   ```

2. **Batch context for tracking**
   ```php
   $batchId = Str::uuid();
   foreach ($images as $img) {
       $agent->analyzeAsync($img, 1, 1, context: ['batchId' => $batchId]);
   }
   ```

3. **Pass relevant context to avoid re-processing**
   ```php
   $agent->analyze($imageUrl, 1, 1, context: [
       'userId' => $userId,        // For per-user customization
       'locale' => 'en_US',        // Localized output
       'currency' => 'USD',        // Price formatting
   ]);
   ```

4. **Cache credential lookups**
   ```php
   $agent->analyze($imageUrl, 1, 1);
   // Credential fetched once, reused for all properties
   ```

---

## Retry Logic

Failed async jobs are retried 3 times with 30-second backoff:

```php
// In ExecuteAgentJob::class
public int $tries = 3;
public int $backoff = 30;  // seconds
```

Failed jobs still logged in `AgentExecution`:
```php
AgentExecution::where('status', 'failed')
    ->where('error', 'LIKE', '%Connection timeout%')
    ->delete();  // Clean up old failures
```

---

## Testing

```php
use Mockery as m;
use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\DTOs\AgentResult;

public function test_image_analysis()
{
    $mockAgent = m::mock(ImageProductAgent::class);
    $mockAgent->shouldReceive('analyze')
        ->andReturn(AgentResult::success(
            output: 'test',
            data: ['name' => 'Test Product', 'colors' => ['blue']],
        ));

    $this->app->instance(ImageProductAgent::class, $mockAgent);

    $response = $this->post('/api/products/analyze', [
        'imageUrl' => 'https://example.com/img.jpg',
    ]);

    $response->assertJson(['name' => 'Test Product']);
}
```

---

## Extending ImageProductAgent

Create a custom agent by extending:

```php
namespace App\Agents;

use Webkul\AiAgent\Agents\ImageProductAgent;

class CustomImageAgent extends ImageProductAgent
{
    protected function prepareImageContent(string $imageSource): string
    {
        // Custom image preprocessing
        // Add watermark removal, quality scoring, etc.
        return parent::prepareImageContent($imageSource);
    }

    protected function buildInstruction(string $imageContent): string
    {
        // Custom prompt engineering
        return "Analyze this [custom format] image: ...";
    }
}
```

Use it:
```php
$customAgent = app(CustomImageAgent::class);
$result = $customAgent->analyze($imageUrl, 1, 1);
```

---

## Key Classes

| Class | Purpose |
|-------|---------|
| `ImageProductAgent` | Main agent for image analysis |
| `AgentResult` | DTO carrying execution result |
| `AgentPayload` | DTO flowing through pipeline |
| `AgentService` | Orchestrator (injected internally) |
| `AgentExecution` | Model storing execution history |

---

See [AGENTS.md](AGENTS.md) for full architecture documentation and [USAGE_EXAMPLES.md](USAGE_EXAMPLES.md) for 8 detailed patterns.
