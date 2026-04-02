<?php

namespace Webkul\AiAgent\Services;

use Illuminate\Http\UploadedFile;
use Webkul\AiAgent\DTOs\ImageProductContext;
use Webkul\AiAgent\Exceptions\ApiException;
use Webkul\AiAgent\Http\Client\AiApiClient;

/**
 * Small, focused agent that accepts a product image upload and:
 *
 *   1. Validates + stores the uploaded file
 *   2. Sends it to VisionService for AI analysis
 *   3. Enriches sparse attributes via a second AI call
 *   4. Creates the product in Unopim
 *
 * This is the single entry-point the controller calls.
 */
class ImageToProductService
{
    public function __construct(
        protected VisionService $visionService,
        protected EnrichmentService $enrichmentService,
        protected ProductWriterService $productWriterService,
    ) {}

    /**
     * Execute the full image → product flow.
     *
     * @param  UploadedFile  $image  Uploaded image file
     * @param  AiApiClient|null  $apiClient  Pre-configured API client (uses selected platform/model)
     * @param  int  $credentialId  AI credential to use (ignored when $apiClient is provided)
     * @param  array{
     *     locale?: string,
     *     channel?: string,
     *     sku?: string,
     *     family?: string,
     * }  $options  Optional overrides
     * @return ImageProductContext The fully populated context
     *
     * @throws ApiException
     * @throws \InvalidArgumentException
     */
    public function execute(
        UploadedFile $image,
        ?AiApiClient $apiClient = null,
        int $credentialId = 0,
        array $options = [],
    ): ImageProductContext {
        // 1 — Capture mime type before storing (file may move)
        $mimeType = $image->getMimeType() ?: 'image/jpeg';

        // 2 — Store the uploaded image
        $storedPath = $this->storeImage($image);

        // 3 — Vision: analyze the image, get attributes + category
        $ctx = $this->visionService->analyze(
            imageContent: $this->toBase64DataUri($storedPath, $mimeType),
            credentialId: $credentialId,
            apiClient: $apiClient,
            options: [
                'locale'       => $options['locale'] ?? 'en',
                'maxAttempts'  => 3,
                'temperature'  => 0.2,
            ],
        );

        // Keep the real stored path (not the data URI)
        $ctx = $ctx->withImagePath($storedPath);

        // 4 — Enrich: fill missing attributes (name, description, SEO, etc.)
        $ctx = $this->enrichmentService->enrich(
            ctx: $ctx,
            credentialId: $credentialId,
            apiClient: $apiClient,
            options: $options,
        );

        // 5 — Write: create the product in Unopim
        $ctx = $this->productWriterService->createProduct($ctx, $options);

        return $ctx;
    }

    /**
     * Store the uploaded image in the public disk under ai-agent/images/.
     */
    protected function storeImage(UploadedFile $image): string
    {
        $path = $image->store('ai-agent/images', 'public');

        return storage_path('app/public/'.$path);
    }

    /**
     * Read a local file and return a base64 data URI.
     */
    protected function toBase64DataUri(string $filePath, ?string $mimeType = null): string
    {
        if (! file_exists($filePath)) {
            throw new \InvalidArgumentException("Image file not found: {$filePath}");
        }

        $raw = file_get_contents($filePath);

        if ($raw === false || strlen($raw) === 0) {
            throw new \InvalidArgumentException("Failed to read image file: {$filePath}");
        }

        // Auto-detect mime type if not provided or unreliable
        if (empty($mimeType) || $mimeType === 'application/octet-stream') {
            $mimeType = mime_content_type($filePath) ?: 'image/jpeg';
        }

        return 'data:'.$mimeType.';base64,'.base64_encode($raw);
    }
}
