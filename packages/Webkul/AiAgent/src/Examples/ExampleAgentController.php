<?php

namespace Webkul\AiAgent\Examples;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Webkul\AiAgent\Agents\ImageProductAgent;
use Webkul\AiAgent\Agents\ProductCategorizerAgent;
use Webkul\AiAgent\Agents\TextDescriptionAgent;

/**
 * Example controller demonstrating agent usage with dependency injection.
 *
 * Shows how to inject agents and execute them synchronously or asynchronously.
 *
 * Register in routes:
 *   Route::post('ai-agent/analyze-image', [ExampleAgentController::class, 'analyzeImage'])
 *   Route::post('ai-agent/generate-description', [ExampleAgentController::class, 'generateDescription'])
 */
class ExampleAgentController extends Controller
{
    /**
     * Analyze a product image.
     *
     * POST /api/ai-agent/analyze-image
     * {
     *   "imageUrl": "https://...",
     *   "agentId": 1,
     *   "credentialId": 1,
     *   "async": false
     * }
     */
    public function analyzeImage(
        ImageProductAgent $agent,
    ): JsonResponse {
        $imageUrl = request('imageUrl');
        $agentId = (int) request('agentId');
        $credentialId = (int) request('credentialId');
        $async = (bool) request('async', false);

        try {
            if ($async) {
                $agent->analyzeAsync(
                    imageSource: $imageUrl,
                    agentId: $agentId,
                    credentialId: $credentialId,
                );

                return response()->json([
                    'message' => 'Image analysis queued.',
                    'status'  => 'queued',
                ]);
            }

            $result = $agent->analyze(
                imageSource: $imageUrl,
                agentId: $agentId,
                credentialId: $credentialId,
            );

            return response()->json($result->toArray());
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Generate product description from text input.
     *
     * POST /api/ai-agent/generate-description
     * {
     *   "productTitle": "Nike Air Max",
     *   "specifications": {...},
     *   "agentId": 2,
     *   "credentialId": 1
     * }
     */
    public function generateDescription(
        TextDescriptionAgent $agent,
    ): JsonResponse {
        $input = [
            'title'          => request('productTitle'),
            'specifications' => request('specifications', []),
        ];

        $agentId = (int) request('agentId');
        $credentialId = (int) request('credentialId');

        $result = $agent->execute(
            input: $input,
            agentId: $agentId,
            credentialId: $credentialId,
            context: ['locale' => app()->getLocale()],
        );

        return response()->json($result->toArray());
    }

    /**
     * Categorize a product.
     *
     * POST /api/ai-agent/categorize-product
     * {
     *   "productData": {...},
     *   "agentId": 3,
     *   "credentialId": 1
     * }
     */
    public function categorizeProduct(
        ProductCategorizerAgent $agent,
    ): JsonResponse {
        $productData = request('productData', []);
        $agentId = (int) request('agentId');
        $credentialId = (int) request('credentialId');

        $result = $agent->execute(
            input: $productData,
            agentId: $agentId,
            credentialId: $credentialId,
        );

        return response()->json($result->toArray());
    }
}
