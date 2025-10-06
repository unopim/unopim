<?php

namespace Webkul\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Webkul\Admin\DataGrids\MagicPromptGrid;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob;
use Webkul\MagicAI\Jobs\SaveTranslatedDataJob;
use Webkul\MagicAI\Repository\MagicPromptRepository;
use Webkul\MagicAI\Services\AIModel;
use Webkul\MagicAI\Services\Prompt\Prompt;
use Webkul\Product\Facades\ProductValueMapper as ProductValueMapperFacade;
use Webkul\Product\Repositories\ProductRepository;

class MagicAIController extends Controller
{
    public function __construct(
        protected ProductRepository $productRepository,
        protected AttributeRepository $attributeRepository,
        protected CategoryFieldRepository $categoryFieldRepository,
        protected MagicPromptRepository $magicPromptRepository,
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
     * Validate the AI credential.
     */
    public function validateCredential(): JsonResponse
    {
        $this->validate(request(), [
            'api_domain' => 'required',
        ]);

        try {
            return new JsonResponse([
                'models'  => AIModel::validate(),
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
            $data = $this->categoryFieldRepository->getCategoryFieldListBySearch($query, ['code', 'name'], excludeTypes: ['image', 'file']);
        } else {
            $data = $this->attributeRepository->getAttributeListBySearch($query, ['code', 'name'], excludeTypes: ['image', 'gallery', 'file', 'asset']);
        }

        $data = array_map(function ($item) {
            return [
                'code' => $item->code,
                'name' => $item->name ? $item->name : '['.$item->code.']',
            ];
        }, $data);

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
            $locale = core()->getRequestedLocaleCode();
            $prompt = request()->input('prompt');

            $prompt .= "\n\nGenerated content should be in {$locale}.";
            $prompt = $this->promptService->getPrompt(
                $prompt,
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
        if (request()->field == 'category_field') {
            $prompts = $this->magicPromptRepository->where('type', 'category')->select('prompt', 'title')->get();

            return new JsonResponse([
                'prompts' => $prompts,
            ]);
        }
        $prompts = $this->magicPromptRepository->where('type', 'product')->select('prompt', 'title')->get();

        return new JsonResponse([
            'prompts' => $prompts,
        ]);
    }

    public function index()
    {
        if (request()->ajax()) {
            return app(MagicPromptGrid::class)->toJson();
        }

        return view('admin::configuration.magic-ai-prompt.index');
    }

    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'prompt' => 'required',
            'title'  => 'required',
            'type'   => 'required',
        ]);

        $data = request()->only([
            'prompt',
            'title',
            'type',
        ]);

        $this->magicPromptRepository->create($data);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.prompt.message.save-success'),
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $prompt = $this->magicPromptRepository->findOrFail($id);

        return new JsonResponse([
            'data' => $prompt,
        ]);
    }

    public function update(): JsonResponse
    {
        $this->validate(request(), [
            'prompt'      => 'required',
            'title'       => 'required',
            'type'        => 'required',
        ]);

        $data = request()->only(['prompt', 'title', 'type']);
        $this->magicPromptRepository->update($data, request()->id);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.prompt.message.update-success'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->magicPromptRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.prompt.message.delete-success'),
            ]);
        } catch (\Exception $e) {
            Log::info($e);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.prompt.message.delete-fail'),
            ], 500);
        }
    }

    public function isTranslatable()
    {
        $productId = request()->resource_id;
        $product = $this->productRepository->find($productId);
        $productData = $product->toArray();
        $locale = core()->getRequestedLocaleCode();
        $channel = core()->getRequestedChannelCode();
        $arr = ProductValueMapperFacade::getChannelLocaleSpecificFields($productData, $channel, $locale);

        return new JsonResponse([
            'isTranslatable' => ! empty($arr) && array_key_exists(request()->field, $arr),
            'sourceData'     => ! empty($arr) && array_key_exists(request()->field, $arr) ? $arr[request()->field] : null,
        ]);
    }

    public function translateToManyLocale(): JsonResponse
    {
        $field = request()->input('field');
        $targetLocales = explode(',', request()->input('targetLocale'));
        $translatedData = [];
        foreach ($targetLocales as $locale) {
            $p = "Translate @$field into $locale. Return only the translated value wrapped in a single <p> tag. Do not include any additional text, descriptions, or explanations.";
            $prompt = $this->promptService->getPrompt(
                $p,
                request()->input('resource_id'),
                request()->input('resource_type')
            );

            $response = MagicAI::setModel(request()->input('model'))
                ->setPlatForm(core()->getConfigData('general.magic_ai.settings.ai_platform'))
                ->setPrompt($prompt)
                ->ask();
            preg_match_all('/<p>(.*?)<\/p>/', $response, $matches);

            $value = end($matches[1]);
            $translatedData[] = [
                'locale'  => $locale,
                'content' => $value,
            ];
        }

        return new JsonResponse([
            'translatedData' => $translatedData,
        ]);
    }

    public function saveTranslatedData()
    {
        $id = request()->resource_id;
        $translatedData = json_decode(request()->translatedData, true);
        $channel = request()->input('targetChannel');
        $field = request()->input('field');

        SaveTranslatedDataJob::dispatch($id, $translatedData, $channel, $field);

        return response()->json(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
    }

    public function isAllAttributeTranslatable()
    {
        $productId = request()->resource_id;
        $product = $this->productRepository->find($productId);
        $productData = $product->toArray();
        $locale = core()->getRequestedLocaleCode();
        $channel = core()->getRequestedChannelCode();
        $arr = ProductValueMapperFacade::getChannelLocaleSpecificFields($productData, $channel, $locale);
        $sourceField = explode(',', request()->input('attributes'));
        $result = [];
        foreach ($sourceField as $field) {
            if (! empty($arr) && array_key_exists($field, $arr)) {
                $attribute = $this->attributeRepository->where('code', $field)->first();
                $result[] = [
                    'fieldLabel'     => $attribute->name,
                    'fieldName'      => $field,
                    'isTranslatable' => ! empty($arr) && array_key_exists($field, $arr),
                    'sourceData'     => ! empty($arr) && array_key_exists($field, $arr) ? $arr[$field] : null,
                    'translatedData' => null,
                ];
            }
        }

        return $result;
    }

    public function translateAllAttribute()
    {
        $attributes = $this->isAllAttributeTranslatable();

        foreach ($attributes as $key => $attribute) {
            $field = $attribute['fieldName'];
            $type = $this->attributeRepository->findByField('code', $field)->first()->type;
            $attributes[$key]['type'] = $type;

            if ($attribute['isTranslatable'] != false) {
                $targetLocales = explode(',', request()->input('targetLocale'));
                $translatedData = [];

                foreach ($targetLocales as $locale) {
                    $value = null;
                    $p = "Translate @$field into $locale. Return only the translated value wrapped in a single <p> tag. Do not include any additional text, descriptions, or explanations.";
                    $prompt = $this->promptService->getPrompt(
                        $p,
                        request()->input('resource_id'),
                        request()->input('resource_type')
                    );

                    $response = MagicAI::setModel(request()->input('model'))
                        ->setPlatForm(core()->getConfigData('general.magic_ai.settings.ai_platform'))
                        ->setPrompt($prompt)
                        ->ask();

                    preg_match_all('/<p>(.*?)<\/p>/', $response, $matches);
                    $value = end($matches[1]);

                    $translatedData[] = [
                        'locale'  => $locale,
                        'content' => $value,
                    ];
                }

                $attributes[$key]['translatedData'] = $translatedData;
            }
        }

        return new JsonResponse($attributes);
    }

    public function saveAllTranslatedAttributes()
    {
        $productId = request()->resource_id;
        $translatedValues = json_decode(request()->translatedData, true);
        $channel = request()->input('targetChannel');

        SaveTranslatedAllAttributesJob::dispatch($productId, $translatedValues, $channel);

        return response()->json(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
    }
}
