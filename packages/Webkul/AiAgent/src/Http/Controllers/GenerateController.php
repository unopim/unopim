<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Webkul\AiAgent\Http\Requests\GenerateForm;
use Webkul\AiAgent\Repositories\CredentialRepository;
use Webkul\AiAgent\Services\ImageToProductService;

class GenerateController extends Controller
{
    public function __construct(
        protected CredentialRepository $credentialRepository,
        protected ImageToProductService $imageToProductService,
    ) {}

    /**
     * Show the Generate page.
     *
     * @return View
     */
    public function index()
    {
        $credentials = $this->credentialRepository->getActiveList();

        return view('ai-agent::generate.index', compact('credentials'));
    }

    /**
     * Process uploaded images and generate a product.
     */
    public function process(GenerateForm $request): JsonResponse
    {
        $validated = $request->validated();
        $images = $validated['images'];
        $firstImage = $images[0];

        try {
            $ctx = $this->imageToProductService->execute(
                image: $firstImage,
                credentialId: (int) $validated['credential_id'],
                options: [
                    'locale'      => app()->getLocale(),
                    'instruction' => $validated['instruction'] ?? '',
                ],
            );

            // Process additional images (attach to the same product context)
            // For v1 we use only the first image for analysis but all could
            // be stored as gallery images in a future iteration.

            $overallConfidence = $ctx->overallConfidence();

            return new JsonResponse([
                'message' => trans('ai-agent::app.generate.success'),
                'data'    => [
                    'detected_product' => $ctx->detectedProduct,
                    'category'         => $ctx->category,
                    'attributes'       => $ctx->attributes,
                    'enrichment'       => $ctx->enrichment,
                    'confidence'       => $overallConfidence,
                    'product_id'       => $ctx->productId,
                    'image_path'       => $ctx->imagePath,
                ],
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
