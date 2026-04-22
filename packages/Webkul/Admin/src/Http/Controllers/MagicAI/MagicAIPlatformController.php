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
use Webkul\AiAgent\Chat\PrismErrorResolver;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Support\ModelRecommender;

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

        $this->ensureDefaultPlatformIsEnabled($data);

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

        $this->ensureDefaultPlatformIsEnabled($data);

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
                return new JsonResponse([
                    'message' => trans('admin::app.configuration.platform.message.cannot-delete-default'),
                ], JsonResponse::HTTP_BAD_REQUEST);
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

            // Custom providers reuse the Groq SDK under the hood, so without an
            // explicit api_url the request would silently hit Groq's default
            // endpoint and ship the caller's API key to the wrong host.
            if ($provider === AiProvider::Custom && empty(trim((string) request()->input('api_url')))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => trans('admin::app.configuration.platform.message.test-fail').': '.trans('admin::app.configuration.platform.message.custom-api-url-required'),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->configureProviderFromRequest($provider);

            $models = array_values(array_filter(array_map(
                'trim',
                explode(',', (string) request()->input('models'))
            )));

            // Pick a model that actually supports text completion. Image-only
            // models like chatgpt-image-latest / dall-e-3 would make the test
            // fail with "model not found" even though the API key is valid.
            $model = ModelRecommender::pickTextModel($models);

            if ($model === null || $model === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => trans('admin::app.configuration.platform.message.test-fail').': '.trans('admin::app.configuration.platform.message.no-test-model'),
                ], JsonResponse::HTTP_BAD_REQUEST);
            }

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
        } catch (\Throwable $e) {
            $resolved = PrismErrorResolver::resolve($e);

            // Always use the resolver's message: known errors get the localized
            // user-friendly text (rate limit / overloaded / too large), and
            // unknown errors now go through sanitizeRawMessage which extracts
            // the underlying upstream HTTP response body when Prism's own
            // message is just an "Unknown error" placeholder.
            $detail = $resolved['message'];

            // Custom platforms route through Prism's Groq class for the
            // /chat/completions endpoint. The Groq class hardcodes "Groq Error"
            // in the exception text, which is misleading when the actual HTTP
            // call went to (e.g.) Cerebras. Rewrite the prefix so the message
            // accurately reflects the user's selected provider.
            if (request()->input('provider') === AiProvider::Custom->value) {
                $detail = preg_replace('/^Groq Error\b/', 'Custom Provider Error', $detail);
            }

            return new JsonResponse([
                'success' => false,
                'message' => trans('admin::app.configuration.platform.message.test-fail').': '.$detail,
            ], $resolved['is_known'] ? $resolved['status'] : JsonResponse::HTTP_BAD_REQUEST);
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

            // Pick recommended models to auto-select (includes image models)
            $recommended = ModelRecommender::recommend($models);

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
     * Validate each model name in the comma-separated models string.
     *
     * @throws ValidationException
     */
    protected function validateModelNames(string &$models): void
    {
        $modelList = array_map(fn ($m) => ltrim(trim($m), '~'), explode(',', $models));
        $models = implode(',', $modelList);
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
     * A platform cannot be marked default unless it is also enabled.
     *
     * @throws ValidationException
     */
    protected function ensureDefaultPlatformIsEnabled(array $data): void
    {
        if (! empty($data['is_default']) && empty($data['status'])) {
            throw ValidationException::withMessages([
                'is_default' => trans('admin::app.configuration.platform.message.default-requires-enabled'),
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
     *
     * Writes to both the laravel/ai (`ai.providers.*`) and Prism
     * (`prism.providers.*`) config namespaces so Test Connection honours
     * a custom `api_url` regardless of which SDK ends up making the call.
     */
    protected function configureProviderFromRequest(AiProvider $provider): void
    {
        $configKey = $provider->configKey();
        $apiKey = $this->resolveApiKey();

        config([
            "ai.providers.{$configKey}.key"        => $apiKey,
            "prism.providers.{$configKey}.api_key" => $apiKey,
        ]);

        if (request()->input('api_url')) {
            config([
                "ai.providers.{$configKey}.url"    => request()->input('api_url'),
                "prism.providers.{$configKey}.url" => request()->input('api_url'),
            ]);
        }

        $extras = request()->input('extras');
        if ($extras) {
            $decoded = is_string($extras) ? json_decode($extras, true) : $extras;
            if (is_array($decoded)) {
                foreach ($decoded as $key => $value) {
                    config([
                        "ai.providers.{$configKey}.{$key}"    => $value,
                        "prism.providers.{$configKey}.{$key}" => $value,
                    ]);
                }
            }
        }
    }
}
