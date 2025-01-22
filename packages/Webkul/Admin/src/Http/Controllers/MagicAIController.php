<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Services\AIModel;
use Webkul\MagicAI\Services\Prompt\Prompt;

class MagicAIController extends Controller
{
    public function __construct(
        protected AttributeRepository $attributeRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected Prompt $promptService,
    ) {}

    /**
     * Get the AI model API.
     */
    public function model(): JsonResponse
    {
        try {
            return new JsonResponse([
                'models'  => AIModel::getModels(),
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-error'),
            ], 500);
        }
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
     * Get the suggestion Attributes|Category-Field.
     */
    public function suggestionValues(): JsonResponse
    {
        $query = request()->input('query');
        $entityName = request()->input('entity_name', 'attribute');

        if ($entityName === 'category_field') {
            $data = $this->categoryFieldRepository->getCategoryFieldListBySearch($query, ['code', 'name']);
        } else {
            $data = $this->attributeRepository->getAttributeListBySearch($query, ['code', 'name']);
        }

        return new JsonResponse($data);
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
            $prompt = $this->promptService->getPrompt(
                request()->input('prompt'),
                request()->input('resource_id'),
                request()->input('resource_type')
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

        if (core()->getConfigData('general.magic_ai.settings.ai_platform') != 'openai') {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.magic-ai-openai-required'),
            ], 500);
        }

        try {
            $options = request()->only([
                'n',
                'size',
                'quality',
            ]);

            $prompt = $this->promptService->getPrompt(
                request()->input('prompt'),
                request()->input('resource_id'),
                request()->input('resource_type')
            );

            $images = MagicAI::setModel(request()->input('model'))
                ->setPlatForm(core()->getConfigData('general.magic_ai.settings.ai_platform'))
                ->setPrompt($prompt)
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

    public function defaultPrompt()
    {
        $prompts = config('default_prompts');
        $translatedPrompts = [];

        foreach ($prompts as $value) {
            $translatedPrompts[] = [
                'prompt' => trans($value['prompt']),
                'title'  => trans($value['title']),
            ];
        }

        return new JsonResponse([
            'prompts' => $translatedPrompts,
        ]);
    }
}
