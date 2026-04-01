<?php

namespace Webkul\AiAgent\Agents;

use Webkul\AiAgent\Contracts\AgentServiceContract;
use Webkul\AiAgent\DTOs\AgentPayload;
use Webkul\AiAgent\DTOs\AgentResult;

/**
 * Concrete agent for analyzing product images and extracting structured data.
 *
 * Accepts an image (URL or file path) and runs the configured pipeline
 * to extract product information (name, description, category, SKU, etc.).
 *
 * Usage:
 *   $agent = app(ImageProductAgent::class);
 *   $result = $agent->analyze('https://example.com/product.jpg', agentId: 1, credentialId: 1);
 *   if ($result->success) {
 *       $productData = $result->data;
 *   }
 */
class ImageProductAgent
{
    /**
     * Default system prompt for product image analysis.
     */
    protected const DEFAULT_SYSTEM_PROMPT = <<<'PROMPT'
        You are an expert product analyst. Analyze the product image and extract structured data.
        Return valid JSON with the following fields:
        - name: string (product name)
        - description: string (detailed description)
        - category: string (product category)
        - price: number (estimated price in USD, optional)
        - colors: array of strings (detected colors)
        - materials: array of strings (detected materials)
        - estimatedSize: string (XS, S, M, L, XL, or dimensions)
        - quality: string (premium, standard, budget)
        - recommendedUses: array of strings (use cases)
        - keyFeatures: array of strings (standout features)
        PROMPT;

    public function __construct(
        protected AgentServiceContract $agentService,
    ) {}

    /**
     * Analyze a product image and extract structured data.
     *
     * @param  string  $imageSource  Image URL or file path
     * @param  int  $agentId  Agent configuration ID
     * @param  int  $credentialId  AI provider credential ID
     * @param  array<string, mixed>  $additionalContext  Extra context to pass to the agent
     */
    public function analyze(
        string $imageSource,
        int $agentId,
        int $credentialId,
        array $additionalContext = [],
    ): AgentResult {
        $imageContent = $this->prepareImageContent($imageSource);

        $context = array_merge([
            'imageSource' => $imageSource,
            'imageType'   => $this->detectImageType($imageSource),
        ], $additionalContext);

        $payload = new AgentPayload(
            agentId: $agentId,
            credentialId: $credentialId,
            instruction: $this->buildInstruction($imageContent),
            context: $context,
        );

        return $this->agentService->execute($payload);
    }

    /**
     * Analyze a product image asynchronously (queued).
     *
     * @param  array<string, mixed>  $additionalContext
     */
    public function analyzeAsync(
        string $imageSource,
        int $agentId,
        int $credentialId,
        array $additionalContext = [],
    ): void {
        $imageContent = $this->prepareImageContent($imageSource);

        $context = array_merge([
            'imageSource' => $imageSource,
            'imageType'   => $this->detectImageType($imageSource),
        ], $additionalContext);

        $payload = new AgentPayload(
            agentId: $agentId,
            credentialId: $credentialId,
            instruction: $this->buildInstruction($imageContent),
            context: $context,
        );

        $this->agentService->executeAsync($payload);
    }

    /**
     * Prepare image content for the AI provider.
     *
     * Converts image file paths to base64, validates URLs, or passes through URLs directly.
     *
     * @return string Base64-encoded data or image URL
     */
    protected function prepareImageContent(string $imageSource): string
    {
        // If it's a URL, validate and return as-is
        if ($this->isUrl($imageSource)) {
            if (! $this->isValidUrl($imageSource)) {
                throw new \InvalidArgumentException('Invalid image URL: '.$imageSource);
            }

            return $imageSource;
        }

        // If it's a file path, read and encode as base64
        if ($this->isFilePath($imageSource)) {
            if (! file_exists($imageSource)) {
                throw new \InvalidArgumentException('Image file not found: '.$imageSource);
            }

            if (! $this->isAllowedImageMime($imageSource)) {
                throw new \InvalidArgumentException('Unsupported image format: '.$imageSource);
            }

            return $this->encodeImageToBase64($imageSource);
        }

        // If it looks like base64, pass through
        if ($this->isBase64($imageSource)) {
            return $imageSource;
        }

        throw new \InvalidArgumentException('Image source must be a URL, file path, or base64 string.');
    }

    /**
     * Build the instruction prompt for the AI with image content.
     */
    protected function buildInstruction(string $imageContent): string
    {
        // For URLs or base64, reference format depends on provider
        // This is a generic format; providers adapt accordingly
        return <<<PROMPT
            Analyze this product image:

            [IMAGE: $imageContent]

            Extract all relevant product data and return as valid JSON.
            PROMPT;
    }

    /**
     * Detect image type from source (URL or file extension).
     *
     * @return string One of: 'url', 'base64', 'file'
     */
    protected function detectImageType(string $imageSource): string
    {
        if ($this->isUrl($imageSource)) {
            return 'url';
        }

        if ($this->isBase64($imageSource)) {
            return 'base64';
        }

        return 'file';
    }

    /**
     * Check if source is a URL.
     */
    protected function isUrl(string $source): bool
    {
        return preg_match('~^https?://~i', $source) === 1;
    }

    /**
     * Check if source is a file path.
     */
    protected function isFilePath(string $source): bool
    {
        return file_exists($source) || preg_match('~^/|^~', $source);
    }

    /**
     * Check if source is base64 encoded.
     */
    protected function isBase64(string $source): bool
    {
        if (! preg_match('~^[A-Za-z0-9+/]+=*$~', $source)) {
            return false;
        }

        return strlen($source) % 4 === 0;
    }

    /**
     * Validate that a URL is accessible.
     */
    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Check if file MIME type is allowed.
     */
    protected function isAllowedImageMime(string $filePath): bool
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mimeType = mime_content_type($filePath);

        return in_array($mimeType, $allowedMimes, true);
    }

    /**
     * Encode image file to base64 data URI.
     */
    protected function encodeImageToBase64(string $filePath): string
    {
        $imageData = file_get_contents($filePath);

        if ($imageData === false) {
            throw new \RuntimeException('Failed to read image file: '.$filePath);
        }

        $mimeType = mime_content_type($filePath);

        return 'data:'.$mimeType.';base64,'.base64_encode($imageData);
    }
}
