<?php

namespace Webkul\Admin\Http\Controllers\MagicAI;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\MagicAI\MagicPromptGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MagicAI\ContentGenerationRequest;
use Webkul\Admin\Http\Requests\MagicAI\ImageGenerationRequest;
use Webkul\Admin\Http\Requests\MagicAI\MagicPromptRequest;
use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\MagicAI\Facades\MagicAI;
use Webkul\MagicAI\Jobs\SaveTranslatedAllAttributesJob;
use Webkul\MagicAI\Jobs\SaveTranslatedDataJob;
use Webkul\MagicAI\MagicAI as MagicAIService;
use Webkul\MagicAI\Models\MagicAISystemPrompt;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Repository\MagicAISystemPromptRepository;
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
        protected MagicAISystemPromptRepository $magicAiSystemPromptRepository,
        protected MagicAIPlatformRepository $platformRepository,
        protected Prompt $promptService,
    ) {}

    public function model(): JsonResponse
    {
        try {
            $platformId = request()->input('platform_id');
            $type = request()->input('type'); // 'image' to filter image models

            $models = $platformId
                ? AIModel::getModelsForPlatform((int) $platformId)
                : AIModel::getModels();

            if ($type === 'image') {
                $models = AIModel::filterImageModels($models, $platformId ? (int) $platformId : null);
            }

            return new JsonResponse([
                'models'  => $models,
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-error'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate the AI credential.
     */
    public function validateCredential(): JsonResponse
    {
        try {
            return new JsonResponse([
                'models'  => AIModel::validate(),
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.catalog.products.index.magic-ai-validate-error'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function availableModel(): JsonResponse
    {
        return new JsonResponse([
            'models' => AIModel::getAvailableModels(),
        ]);
    }

    /**
     * Get active platforms with their models for frontend dropdown.
     */
    public function platforms(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $purpose = request()->input('purpose');
        $platforms = $this->platformRepository->getActivePlatformOptions();

        if ($purpose === 'image_generation') {
            $platforms = array_values(array_filter(
                array_map(function ($platform) {
                    $filtered = AIModel::filterImageModels($platform['models'] ?? [], $platform['id']);

                    if (empty($filtered)) {
                        return null;
                    }

                    $platform['models'] = $filtered;

                    return $platform;
                }, $platforms)
            ));
        }

        return new JsonResponse([
            'platforms' => $platforms,
        ]);
    }

    /**
     * Get the suggestion Attributes|Category-Field.
     */
    public function suggestionValues(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $query = (string) request()->input('query', '');
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
     * Generate AI content.
     */
    public function content(ContentGenerationRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        try {
            $locale = core()->getRequestedLocaleCode();
            $prompt = request()->input('prompt');
            $tone = request()->input('tone');

            // Use editable values if provided, otherwise fall back to system prompt record
            $systemPromptText = request()->input('system_prompt_text');
            $temperature = request()->input('temperature');
            $maxTokens = request()->input('max_tokens');

            if ($systemPromptText !== null) {
                $toneText = $systemPromptText;
                $temperature = (float) ($temperature ?? 0.7);
                $maxTokens = (int) ($maxTokens ?? 1054);
            } else {
                $toneData = MagicAISystemPrompt::where('id', $tone)->first(['tone', 'temperature', 'max_tokens']);

                if ($toneData !== null) {
                    $toneText = $toneData->tone;
                    $temperature = $toneData->temperature;
                    $maxTokens = $toneData->max_tokens;
                } else {
                    $toneText = '';
                    $temperature = (float) ($temperature ?? 0.7);
                    $maxTokens = (int) ($maxTokens ?? 1054);
                }
            }

            $prompt .= "\n\nGenerated content should be in {$locale}.";

            $prompt = $this->promptService->getPrompt(
                $prompt,
                request()->input('resource_id'),
                request()->input('resource_type')
            );

            $magicAi = $this->resolvePlatform();

            $response = $magicAi
                ->setModel(request()->input('model'))
                ->setTemperature($temperature)
                ->setMaxTokens($maxTokens)
                ->setSystemPrompt($toneText)
                ->setPrompt($prompt)
                ->ask();

            return new JsonResponse([
                'content' => $response,
            ]);
        } catch (\Exception $e) {
            report($e);

            $message = $e->getMessage();

            if (str_contains($message, 'cURL error 28') || str_contains($message, 'timed out')) {
                $message = trans('admin::app.configuration.platform.message.ai-response-timeout');
            }

            return new JsonResponse([
                'message' => $message,
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Generate an image.
     */
    public function image(ImageGenerationRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], JsonResponse::HTTP_FORBIDDEN);
        }

        $validated = $request->validated();

        try {
            $prompt = $validated['prompt'];

            if (isset($validated['resource_id'], $validated['resource_type'])) {
                $prompt = $this->promptService->getPrompt(
                    $prompt,
                    $validated['resource_id'],
                    $validated['resource_type']
                );
            }

            $options = array_filter([
                'n'       => $validated['n'] ?? null,
                'size'    => $validated['size'],
                'quality' => $validated['quality'] ?? null,
            ], fn (mixed $value): bool => $value !== null);

            $magicAi = $this->resolvePlatform();

            $images = $magicAi
                ->setModel($validated['model'])
                ->setPrompt($prompt)
                ->images($options);

            return new JsonResponse([
                'images' => $images,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function defaultPrompt(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $type = request()->input('entity_type', 'product');
        $purpose = request()->input('purpose', 'text_generation');

        if (request()->field == 'category_field') {
            $type = 'category';
        }

        $query = $this->magicPromptRepository
            ->where('purpose', $purpose);

        // For text_generation, filter by entity type (product/category)
        if ($purpose === 'text_generation') {
            $query->where('type', $type);
        }

        $prompts = $query->select('prompt', 'title')->get();

        return new JsonResponse([
            'prompts' => $prompts,
        ]);
    }

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(MagicPromptGrid::class)->toJson();
        }

        return view('admin::configuration.magic-ai-prompt.index');
    }

    public function store(MagicPromptRequest $request): JsonResponse
    {
        $data = $request->only([
            'prompt',
            'title',
            'type',
            'purpose',
            'tone',
        ]);

        $this->magicPromptRepository->create($data);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.prompt.message.save-success'),
        ]);
    }

    public function edit(int $id): View|JsonResponse
    {
        $prompt = $this->magicPromptRepository->findOrFail($id);

        if (! request()->expectsJson()) {
            return view('admin::configuration.magic-ai-prompt.edit', compact('prompt'));
        }

        return new JsonResponse([
            'data' => $prompt,
        ]);
    }

    public function update(MagicPromptRequest $request): JsonResponse
    {
        $data = $request->only(['prompt', 'title', 'type', 'purpose', 'tone']);
        $this->magicPromptRepository->update($data, request()->id);

        return new JsonResponse([
            'message'      => trans('admin::app.configuration.prompt.message.update-success'),
            'redirect_url' => route('admin.magic_ai.prompt.index'),
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
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function isTranslatable(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

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
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $field = request()->input('field');
        $targetLocales = explode(',', request()->input('targetLocale'));
        $translatedData = [];

        $magicAi = $this->resolveTranslationPlatform();

        foreach ($targetLocales as $locale) {
            $p = "Translate @$field into $locale. Preserve the original HTML structure (every <p>, <br>, list and inline tag). Return only the translated HTML, with no commentary, no wrapper, and no extra text.";
            $prompt = $this->promptService->getPrompt(
                $p,
                request()->input('resource_id'),
                request()->input('resource_type')
            );

            $response = $magicAi
                ->setModel(request()->input('model'))
                ->setPrompt($prompt)
                ->translate();

            $translatedData[] = [
                'locale'  => $locale,
                'content' => trim($response),
            ];
        }

        return new JsonResponse([
            'translatedData' => $translatedData,
        ]);
    }

    public function saveTranslatedData(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $id = request()->resource_id;
        $translatedData = json_decode(request()->translatedData, true);
        $channel = request()->input('targetChannel');
        $field = request()->input('field');

        SaveTranslatedDataJob::dispatch($id, $translatedData, $channel, $field);

        return response()->json(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
    }

    public function isAllAttributeTranslatable(): array|JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

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

                $result[$field] = [
                    'fieldLabel'     => $attribute->name,
                    'fieldName'      => $field,
                    'isTranslatable' => true,
                    'sourceData'     => $arr[$field],
                    'translatedData' => null,
                    'type'           => $attribute->type,
                ];
            }
        }

        return $result;
    }

    public function translateAllAttribute(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $attributes = $this->isAllAttributeTranslatable();

        if (empty($attributes)) {
            return new JsonResponse([]);
        }

        $responseData = [
            'headers'    => (array_merge(['locale'], array_column($attributes, 'fieldLabel'))),
            'fields'     => $attributes,
            'translated' => [],
        ];

        $targetLocales = explode(',', request()->input('targetLocale'));
        $magicAi = $this->resolveTranslationPlatform();

        foreach ($targetLocales as $locale) {
            $translatedDataForLocale = [];

            foreach ($attributes as $key => $attribute) {
                $field = $attribute['fieldName'];

                $p = "Translate @$field into $locale. Preserve the original HTML structure (every <p>, <br>, list and inline tag). Return only the translated HTML, with no commentary, no wrapper, and no extra text.";

                $prompt = $this->promptService->getPrompt(
                    $p,
                    request()->input('resource_id'),
                    request()->input('resource_type')
                );

                $response = $magicAi
                    ->setModel(request()->input('model'))
                    ->setPrompt($prompt)
                    ->translate();

                $translatedDataForLocale[$field] = [
                    'field'   => $field,
                    'content' => trim($response),
                ];
            }

            $responseData['translated'][$locale] = $translatedDataForLocale;
        }

        return new JsonResponse($responseData);
    }

    public function saveAllTranslatedAttributes(): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent')) {
            return new JsonResponse(['error' => trans('admin::app.common.unauthorized')], 403);
        }

        $productId = request()->resource_id;
        $translatedValues = json_decode(request()->translatedData, true);
        $channel = request()->input('targetChannel');

        SaveTranslatedAllAttributesJob::dispatch($productId, $translatedValues, $channel);

        return response()->json(['message' => trans('admin::app.catalog.products.edit.translate.tranlated-job-processed')]);
    }

    /**
     * Resolve the platform from request or use default.
     */
    protected function resolvePlatform(): MagicAIService
    {
        $platformId = request()->input('platform_id');

        if ($platformId && $platformId !== '0') {
            return $this->setPlatformOrDefault((int) $platformId);
        }

        $configPlatformId = core()->getConfigData('general.magic_ai.settings.ai_platform');

        if ($configPlatformId && $configPlatformId !== '0') {
            return $this->setPlatformOrDefault((int) $configPlatformId);
        }

        return MagicAI::useDefault();
    }

    /**
     * Resolve the translation platform from config or fall back to default.
     */
    protected function resolveTranslationPlatform(): MagicAIService
    {
        $requestPlatformId = request()->input('platform_id');

        if ($requestPlatformId && $requestPlatformId !== '0') {
            return $this->setPlatformOrDefault((int) $requestPlatformId);
        }

        $translationPlatformId = core()->getConfigData('general.magic_ai.translation.ai_platform');

        if ($translationPlatformId && $translationPlatformId !== '0') {
            return $this->setPlatformOrDefault((int) $translationPlatformId);
        }

        return MagicAI::useDefault();
    }

    /**
     * Try to set the requested platform; if it has been deleted (orphan
     * config or stale request id) fall back to the default platform so
     * the user is never blocked by a dangling reference.
     */
    protected function setPlatformOrDefault(int $platformId): MagicAIService
    {
        try {
            return MagicAI::setPlatformId($platformId);
        } catch (ModelNotFoundException $e) {
            return MagicAI::useDefault();
        }
    }
}
