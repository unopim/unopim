<?php

namespace Webkul\AiAgent\Chat\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\Concerns\ChecksPermission;
use Webkul\AiAgent\Chat\Contracts\PimTool;
use Webkul\AiAgent\DTOs\CredentialConfig;
use Webkul\AiAgent\Http\Client\AiApiClient;
use Webkul\AiAgent\Services\VisionService;
use Webkul\MagicAI\Enums\AiProvider;

class AnalyzeImage implements PimTool
{
    public function __construct(
        protected VisionService $visionService,
    ) {}

    public function register(ChatContext $context): Tool
    {
        $visionService = $this->visionService;

        return new class($context, $visionService) extends ContextualTool
        {
            use ChecksPermission;

            public function __construct(ChatContext $context, protected VisionService $visionService)
            {
                parent::__construct($context);
            }

            public function name(): string
            {
                return 'analyze_image';
            }

            public function description(): string
            {
                return 'Analyze an uploaded image to detect product attributes.';
            }

            public function schema(JsonSchema $schema): array
            {
                return [
                    'instruction' => $schema->string()->description('Optional instructions for the analysis (e.g. "This is a laptop", "Focus on the fabric material")'),
                ];
            }

            public function handle(Request $request): string
            {
                if ($denied = $this->denyUnlessAllowed($this->context, 'catalog.products')) {
                    return $denied;
                }

                if (! $this->context->hasImages()) {
                    return json_encode(['error' => 'No image was uploaded. Ask the user to upload an image first.']);
                }

                $imagePath = $this->context->firstImagePath();

                if (! $imagePath || ! file_exists($imagePath)) {
                    return json_encode(['error' => 'Image file not found on disk.']);
                }

                try {
                    $mimeType = mime_content_type($imagePath) ?: 'image/jpeg';
                    $raw = file_get_contents($imagePath);
                    $dataUri = 'data:'.$mimeType.';base64,'.base64_encode($raw);

                    // Configure the AiApiClient with the platform from this chat session
                    $apiClient = resolve(AiApiClient::class);
                    $apiClient->configure(new CredentialConfig(
                        id: $this->context->platform->id,
                        label: $this->context->platform->label,
                        provider: $this->context->platform->provider,
                        apiUrl: $this->context->platform->api_url ?: AiProvider::from($this->context->platform->provider)->defaultUrl(),
                        apiKey: $this->context->platform->api_key,
                        model: $this->context->model,
                    ));

                    $ctx = $this->visionService->analyze(
                        imageContent: $dataUri,
                        credentialId: 0,
                        apiClient: $apiClient,
                        options: [
                            'locale'      => $this->context->locale,
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
            }
        };
    }
}
