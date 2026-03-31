<?php

namespace Webkul\Admin\Http\Controllers\MagicAI;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Prism\Prism\Facades\Prism;
use Webkul\Admin\DataGrids\MagicAI\MagicAIPlatformDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

class MagicAIPlatformController extends Controller
{
    public function __construct(
        protected MagicAIPlatformRepository $platformRepository,
    ) {}

    public function index(): View|JsonResponse
    {
        if (request()->ajax()) {
            return app(MagicAIPlatformDataGrid::class)->toJson();
        }

        $platformCount = $this->platformRepository->findWhere(['status' => true])->count();
        $hasDefault = $this->platformRepository->getDefault() !== null;

        return view('admin::configuration.magic-ai.platform.index', [
            'providerOptions' => AiProvider::options(),
            'platformCount'   => $platformCount,
            'hasDefault'      => $hasDefault,
        ]);
    }

    public function store(): JsonResponse
    {
        $this->validate(request(), [
            'label'      => 'required|string|max:255',
            'provider'   => 'required|string',
            'api_url'    => 'nullable|url|max:500',
            'api_key'    => 'nullable|string',
            'models'     => 'required|string',
            'is_default' => 'sometimes|boolean',
            'status'     => 'sometimes|boolean',
        ]);

        $data = request()->only(['label', 'provider', 'api_url', 'api_key', 'models', 'is_default', 'status']);

        $this->validateModelNames($data['models']);

        if (! isset($data['status'])) {
            $data['status'] = true;
        }

        if (! isset($data['is_default'])) {
            $data['is_default'] = false;
        }

        $extras = request()->input('extras');
        if ($extras) {
            $data['extras'] = is_string($extras) ? json_decode($extras, true) : $extras;
        }

        $this->platformRepository->create($data);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.platform.message.save-success'),
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $platform = $this->platformRepository->findOrFail($id);

        return new JsonResponse([
            'data' => [
                'id'         => $platform->id,
                'label'      => $platform->label,
                'provider'   => $platform->provider,
                'api_url'    => $platform->api_url,
                'api_key'    => $platform->api_key ? '********' : '',
                'models'     => $platform->models,
                'extras'     => $platform->extras ? json_encode($platform->extras) : '',
                'is_default' => $platform->is_default,
                'status'     => $platform->status,
            ],
        ]);
    }

    public function update(int $id): JsonResponse
    {
        $this->validate(request(), [
            'label'      => 'required|string|max:255',
            'provider'   => 'required|string',
            'api_url'    => 'nullable|url|max:500',
            'api_key'    => 'nullable|string',
            'models'     => 'required|string',
            'is_default' => 'sometimes|boolean',
            'status'     => 'sometimes|boolean',
        ]);

        $data = request()->only(['label', 'provider', 'api_url', 'models', 'is_default', 'status']);

        $this->validateModelNames($data['models']);

        if (! isset($data['status'])) {
            $data['status'] = false;
        }

        if (! isset($data['is_default'])) {
            $data['is_default'] = false;
        }

        $apiKey = request()->input('api_key');
        if ($apiKey && ! preg_match('/^\*+$/', $apiKey)) {
            $data['api_key'] = $apiKey;
        }

        $extras = request()->input('extras');
        if ($extras) {
            $data['extras'] = is_string($extras) ? json_decode($extras, true) : $extras;
        }

        $this->platformRepository->update($data, $id);

        return new JsonResponse([
            'message' => trans('admin::app.configuration.platform.message.update-success'),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $platform = $this->platformRepository->findOrFail($id);

            if ($platform->is_default) {
                $otherCount = $this->platformRepository->findWhere(['status' => true])->count();
                if ($otherCount <= 1) {
                    return new JsonResponse([
                        'message' => trans('admin::app.configuration.platform.message.cannot-delete-default'),
                    ], JsonResponse::HTTP_BAD_REQUEST);
                }
            }

            $this->platformRepository->delete($id);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.platform.message.delete-success'),
            ]);
        } catch (\Exception $e) {
            Log::error($e);

            return new JsonResponse([
                'message' => trans('admin::app.configuration.platform.message.delete-fail'),
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function setDefault(int $id): JsonResponse
    {
        $platform = $this->platformRepository->findOrFail($id);

        DB::transaction(function () use ($id) {
            // Unset all other defaults first
            DB::table('magic_ai_platforms')->where('is_default', true)->update(['is_default' => false]);
            // Set the chosen platform as default
            $this->platformRepository->update(['is_default' => true], $id);
        });

        return new JsonResponse([
            'message' => trans('admin::app.configuration.platform.message.set-default-success'),
        ]);
    }

    public function testConnection(): JsonResponse
    {
        $this->validate(request(), [
            'provider' => 'required|string',
            'api_key'  => 'nullable|string',
            'api_url'  => 'nullable',
            'models'   => 'required|string',
        ]);

        try {
            $provider = AiProvider::from(request()->input('provider'));
            $this->configureProviderFromRequest($provider);

            $models = explode(',', request()->input('models'));
            $model = trim($models[0]);

            $response = Prism::text()
                ->using($provider->toPrismProvider(), $model, [
                    'api_key' => $this->resolveApiKey(),
                ])
                ->withMaxTokens(20)
                ->withClientOptions(['timeout' => 15])
                ->withPrompt('Say OK')
                ->asText();

            return new JsonResponse([
                'success' => true,
                'message' => trans('admin::app.configuration.platform.message.test-success'),
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('admin::app.configuration.platform.message.test-fail').': '.$e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function fetchModels(): JsonResponse
    {
        $this->validate(request(), [
            'provider' => 'required|string',
            'api_key'  => 'nullable|string',
            'api_url'  => 'nullable',
        ]);

        try {
            $provider = AiProvider::from(request()->input('provider'));
            $models = $provider->fetchModels(
                $this->resolveApiKey(),
                request()->input('api_url'),
            );

            // Pick top recommended models from the fetched list
            $recommended = $this->pickRecommendedModels($models, $provider);

            return new JsonResponse([
                'models'      => $models,
                'recommended' => $recommended,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => trans('admin::app.configuration.platform.message.fetch-models-fail').': '.$e->getMessage(),
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Pick recommended models from the fetched list.
     * Uses pattern matching to find the latest/best models for each provider.
     */
    protected function pickRecommendedModels(array $models, AiProvider $provider): array
    {
        if (empty($models)) {
            return [];
        }

        // Provider-specific patterns to match the latest flagship models
        $patterns = match ($provider) {
            AiProvider::OpenAI     => ['/^gpt-\d+(\.\d+)?$/i', '/^gpt-\d+(\.\d+)?-mini$/i'],
            AiProvider::Anthropic  => ['/^claude-.*sonnet/i', '/^claude-.*haiku/i'],
            AiProvider::Gemini     => ['/gemini-.*pro/i', '/gemini-.*flash$/i'],
            AiProvider::Groq       => ['/llama.*versatile/i', '/llama.*instant/i'],
            AiProvider::XAI        => ['/^grok-\d+$/i', '/^grok-\d+-mini$/i'],
            AiProvider::Mistral    => ['/mistral-large/i', '/mistral-small/i'],
            AiProvider::DeepSeek   => ['/deepseek-chat/i', '/deepseek-reasoner/i'],
            default                => [],
        };

        if (empty($patterns)) {
            return array_slice($models, 0, 3);
        }

        $recommended = [];

        foreach ($patterns as $pattern) {
            $matches = preg_grep($pattern, $models);

            if (! empty($matches)) {
                // Sort descending so newest version (highest number) comes first
                $sorted = array_values($matches);
                rsort($sorted);
                $recommended[] = $sorted[0];
            }
        }

        // If patterns didn't match enough, pad with first models from list
        if (count($recommended) < 2 && count($models) >= 2) {
            foreach ($models as $model) {
                if (! in_array($model, $recommended)) {
                    $recommended[] = $model;
                }

                if (count($recommended) >= 3) {
                    break;
                }
            }
        }

        return array_values(array_unique($recommended));
    }

    /**
     * Validate each model name in the comma-separated models string.
     *
     * @throws ValidationException
     */
    protected function validateModelNames(string $models): void
    {
        $modelList = array_map('trim', explode(',', $models));
        $invalid = [];

        foreach ($modelList as $model) {
            if ($model === '' || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-._:\/@]+$/', $model)) {
                $invalid[] = $model;
            }
        }

        if (! empty($invalid)) {
            throw ValidationException::withMessages([
                'models' => trans('admin::app.configuration.platform.message.invalid-model-names', [
                    'names' => implode(', ', $invalid),
                ]),
            ]);
        }
    }

    /**
     * Resolve the real API key. If the submitted key is masked (********),
     * fetch the original key from the database record.
     */
    protected function resolveApiKey(): ?string
    {
        $apiKey = request()->input('api_key');
        $platformId = request()->input('id');

        if ($apiKey && preg_match('/^\*+$/', $apiKey) && $platformId) {
            $platform = $this->platformRepository->find($platformId);

            return $platform?->api_key;
        }

        return $apiKey;
    }

    /**
     * Configure the Laravel AI SDK provider from request data.
     */
    protected function configureProviderFromRequest(AiProvider $provider): void
    {
        $configKey = $provider->configKey();

        config([
            "ai.providers.{$configKey}.key" => $this->resolveApiKey(),
        ]);

        if (request()->input('api_url')) {
            config([
                "ai.providers.{$configKey}.url" => request()->input('api_url'),
            ]);
        }

        $extras = request()->input('extras');
        if ($extras) {
            $decoded = is_string($extras) ? json_decode($extras, true) : $extras;
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    config(["ai.providers.{$configKey}.{$key}" => $value]);
                }
            }
        }
    }
}
