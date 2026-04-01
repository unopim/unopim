<?php

namespace Webkul\AiAgent\Chat\Tools;

use Prism\Prism\Tool;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\VisionService;
use Webkul\MagicAI\Enums\AiProvider;

class AnalyzeImage implements PimTool
{
    use ChecksPermission;

    public function __construct(
        protected VisionService $visionService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        return (new Tool)
            ->as('analyze_image')
            ->for('Analyze an uploaded image to detect product attributes.')
            ->withStringParameter('instruction', 'Optional instructions for the analysis (e.g. "This is a laptop", "Focus on the fabric material")')
            ->using(function (?string $instruction = null) use ($context): string {
                if ($denied = $this->denyUnlessAllowed($context, 'catalog.products')) {
                    return $denied;
                }

                if (! $context->hasImages()) {
                    return json_encode(['error' => 'No image was uploaded. Ask the user to upload an image first.']);
                }

                $imagePath = $context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Image file not found on disk.']);
                }

                try {
                    $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
                    $raw = file_get_contents($imagePath);
                    $dataUri = 'data:'.$mimeType.';base64,'.base64_encode($raw);

                    // Configure the AiApiClient with the platform from this chat session
                    $apiClient = app(AiApiClient::class);
                    $apiClient->configure(new CredentialConfig(
                        id: $context->platform->id,
                        label: $context->platform->label,
                        provider: $context->platform->provider,
                        apiUrl: $context->platform->api_url ?: AiProvider::from($context->platform->provider)->defaultUrl(),
                        apiKey: $context->platform->api_key,
                        model: $context->model,
                    ));

                    $ctx = $this->visionService->analyze(
                        imageContent: $dataUri,
                        credentialId: 0,
                        apiClient: $apiClient,
                        options: [
                            'locale'      => $context->locale,
                            'maxAttempts' => 3,
                            'temperature' => 0.2,
                        ],
                    );

                    return json_encode([
                        'detected_product' => $ctx->detectedProduct,
                        'category'         => $ctx->category,
                        'attributes'       => $ctx->attributes,
                        'confidence'       => $ctx->overallConfidence(),
                    ]);
                } catch (\Throwable $e) {
                    return json_encode(['error' => 'Vision analysis failed: '.$e->getMessage()]);
                }
            });
    }
}
