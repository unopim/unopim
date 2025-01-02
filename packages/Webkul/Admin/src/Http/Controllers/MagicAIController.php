<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Attribute\Services\AttributeService;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Services\AIModel;
use Webkul\MagicAI\Services\Product;

class MagicAIController extends Controller
{
    public function __construct(
        protected AttributeService $attributeService,
        protected Product $productService,
    ) {}

    /**
     * Get the AI model API.
     */
    public function model(): JsonResponse
    {
        return new JsonResponse([
            'models'  => AIModel::getModels(),
            'message' => trans('admin::app.catalog.products.index.magic-ai-model-success'),
        ]);
    }

    /**
     * Get the AI available model.
     */
    public function availableModel(): JsonResponse
    {
        return new JsonResponse([
            'models' => AIModel::getAvailableModels(),
        ]);
    }

    /**
     * Get the suggestion Attributes.
     */
    public function suggestionAttributes(): JsonResponse
    {
        $query = request()->input('query');
        $attributes = $this->attributeService->getAttributeListBySearch($query, ['code', 'name']);

        $data = array_map(function ($attribute) {
            return [
                'value' => $attribute->code,
                'key'   => $attribute->name ?? $attribute->code,
            ];
        }, $attributes);

        return new JsonResponse([
            'attributes' => $attributes,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function content(): JsonResponse
    {
        $this->validate(request(), [
            'model'  => 'required',
            'prompt' => 'required',
        ]);

        try {
            $prompt = $this->productService->getPromptWithProductValues(
                request()->input('prompt'),
                (int) request()->input('product_id')
            );

            $response = MagicAI::setModel(request()->input('model'))
                ->setPlatForm(core()->getConfigData('general.magic_ai.settings.ai_platform'))
                ->setPrompt($prompt)
                ->ask();

            return new JsonResponse([
                'content' => $response,
            ]);
        } catch (\Exception $e) {
            report($e);

            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.magic-ai-error'),
            ], 400);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function image(): JsonResponse
    {
        config([
            'openai.api_key'      => core()->getConfigData('general.magic_ai.settings.api_key'),
            'openai.organization' => core()->getConfigData('general.magic_ai.settings.organization'),
        ]);

        $this->validate(request(), [
            'prompt'  => 'required',
            'model'   => 'required|in:dall-e-2,dall-e-3',
            'n'       => 'required_if:model,dall-e-2|integer|min:1|max:10',
            'size'    => 'required|in:1024x1024,1024x1792,1792x1024',
            'quality' => 'required_if:model,dall-e-3|in:standard,hd',
        ]);

        try {
            $options = request()->only([
                'n',
                'size',
                'quality',
            ]);

            $images = MagicAI::setModel(request()->input('model'))
                ->setPrompt(request()->input('prompt'))
                ->images($options);

            return new JsonResponse([
                'images' => $images,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
