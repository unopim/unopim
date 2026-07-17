<?php

namespace Webkul\Admin\Http\Controllers\MagicAI;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Ai\AnonymousAgent;
use Webkul\Admin\DataGrids\MagicAI\MagicAIPlatformDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Admin\Http\Requests\MagicAI\FetchModelsRequest;
use Webkul\Admin\Http\Requests\MagicAI\PlatformRequest;
use Webkul\Admin\Http\Requests\MagicAI\PlatformTestRequest;
use Webkul\AiAgent\Chat\AiErrorResolver;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ScopedProviderConfig;
use Webkul\MagicAI\Support\ModelRecommender;
use Webkul\Webhook\Validators\SafeWebhookUrl;

class MagicAIPlatformController extends Controller
{
    public function __construct(
        protected MagicAIPlatformRepository $platformRepository,
    ) {
        $this->middleware(function ($request, $next) {
            if (! bouncer()->hasPermission('ai-agent.platform')) {
                abort(403);
            }

            return $next($request);
        });
    }

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

    public function store(PlatformRequest $request): JsonResponse
    {
        $data = $request->only(['label', 'provider', 'api_url', 'api_key', 'models', 'is_default', 'status']);

        $this->validateModelNames($data['models']);
        $this->validatePlatformApiUrl($data);

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

        $apiKeyError = $platform->apiKeyError();

        return new JsonResponse([
            'data' => [
                'id'                => $platform->id,
                'label'             => $platform->label,
                'provider'          => $platform->provider,
                'api_url'           => $platform->api_url,
                'api_key'           => $apiKeyError ? '' : ($platform->safeApiKey() ? '********' : ''),
                'models'            => $platform->models,
                'extras'            => $platform->extras ? json_encode($platform->extras) : '',
                'is_default'        => $platform->is_default,
                'status'            => $platform->status,
                'api_key_corrupted' => $apiKeyError !== null,
            ],
            'message' => $apiKeyError
                ? trans('admin::app.configuration.platform.message.api-key-corrupted', ['error' => $apiKeyError])
                : null,
        ]);
    }

    public function update(PlatformRequest $request, int $id): JsonResponse
    {
        if (! $this->platformRepository->find($id)) {
            return new JsonResponse([
                'message' => trans('admin::app.configuration.platform.message.not-found'),
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        $data = request()->only(['label', 'provider', 'api_url', 'models', 'is_default', 'status']);

        $this->validateModelNames($data['models']);
        $this->validatePlatformApiUrl($data);

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
            DB::table('magic_ai_platforms')->where('is_default', true)->update(['is_default' => false]);
            $this->platformRepository->update(['is_default' => true], $id);
        });

        return new JsonResponse([
            'message' => trans('admin::app.configuration.platform.message.set-default-success'),
        ]);
    }

    public function testConnection(PlatformTestRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent.platform')) {
            abort(403);
        }

        if (! $this->isSafeApiUrl(request()->input('api_url'))) {
            return new JsonResponse([
                'success' => false,
                'message' => trans('admin::app.configuration.platform.message.test-fail'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $provider = AiProvider::from(request()->input('provider'));

            // Custom providers route through the openai-compatible driver, so
            // without an explicit api_url the request would fall back to the
            // global env URL and ship the caller's API key to the wrong host.
            if ($provider === AiProvider::Custom && empty(trim((string) request()->input('api_url')))) {
                return new JsonResponse([
                    'success' => false,
                    'message' => trans('admin::app.configuration.platform.message.test-fail').': '.trans('admin::app.configuration.platform.message.custom-api-url-required'),
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }

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

            $agent = new AnonymousAgent(
                instructions: 'You are a connectivity test bot. Reply with the single word OK.',
                messages: [],
                tools: [],
            );

            ScopedProviderConfig::run(
                $provider->configKey(),
                $this->providerOverridesFromRequest(),
                fn () => $agent->prompt(
                    'Say OK',
                    provider: $provider->toLab(),
                    model: $model,
                    timeout: 15,
                ),
            );

            return new JsonResponse([
                'success' => true,
                'message' => trans('admin::app.configuration.platform.message.test-success'),
            ]);
        } catch (\Throwable $e) {
            $resolved = AiErrorResolver::resolve($e);

            // Always use the resolver's message: known errors get the localized
            // user-friendly text (rate limit / overloaded / too large), and
            // unknown errors go through sanitizeRawMessage which extracts the
            // underlying upstream HTTP response body when the gateway's own
            // message is just an "Unknown error" placeholder.
            $detail = $resolved['message'];

            // Custom platforms route through laravel/ai's OpenAI-compatible
            // gateway for the /chat/completions endpoint. Its error messages
            // hardcode the driver's name (e.g. "OpenAI-compatible Error" when
            // the actual HTTP call went to Cerebras). Rewrite the prefix so
            // the message accurately reflects the user's selected provider.
            if (request()->input('provider') === AiProvider::Custom->value) {
                $detail = preg_replace(
                    '/^(OpenAI-compatible|Groq) Error\b/',
                    trans('admin::app.configuration.platform.message.custom-provider-error'),
                    $detail,
                );
            }

            return new JsonResponse([
                'success' => false,
                'message' => trans('admin::app.configuration.platform.message.test-fail').': '.$detail,
            ], $resolved['is_known'] ? $resolved['status'] : JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    public function fetchModels(FetchModelsRequest $request): JsonResponse
    {
        if (! bouncer()->hasPermission('ai-agent.platform')) {
            abort(403);
        }

        if (! $this->isSafeApiUrl(request()->input('api_url'))) {
            return new JsonResponse([
                'message' => trans('admin::app.configuration.platform.message.fetch-models-fail'),
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

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
     * Guard persisted platforms: Custom providers must carry an explicit
     * api_url (otherwise generation would fall back to the global
     * openai-compatible URL and ship the key to an unrelated host), and any
     * supplied api_url must pass the SSRF safety check.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    private function validatePlatformApiUrl(array $data): void
    {
        $apiUrl = trim((string) ($data['api_url'] ?? ''));

        if (($data['provider'] ?? null) === AiProvider::Custom->value && $apiUrl === '') {
            throw ValidationException::withMessages([
                'api_url' => trans('admin::app.configuration.platform.message.custom-api-url-required'),
            ]);
        }

        if ($apiUrl !== '' && ! $this->isSafeApiUrl($apiUrl)) {
            throw ValidationException::withMessages([
                'api_url' => trans('admin::app.configuration.platform.message.unsafe-api-url'),
            ]);
        }
    }

    private function isSafeApiUrl(?string $apiUrl): bool
    {
        $apiUrl = trim((string) $apiUrl);

        return $apiUrl === '' || SafeWebhookUrl::validate($apiUrl)['valid'];
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

            return $platform?->safeApiKey();
        }

        return $apiKey;
    }

    /**
     * Build the laravel/ai provider overrides from request data so Test
     * Connection honours custom api_url / api_key / extras for the chosen
     * platform. Applied via ScopedProviderConfig for the test call only.
     *
     * @return array<string, mixed>
     */
    protected function providerOverridesFromRequest(): array
    {
        $overrides = [
            'key' => $this->resolveApiKey(),
        ];

        if (request()->input('api_url')) {
            $overrides['url'] = request()->input('api_url');
        }

        $extras = request()->input('extras');
        if ($extras) {
            $decoded = is_string($extras) ? json_decode($extras, true) : $extras;
            if (is_array($decoded)) {
                $overrides = array_merge($overrides, $decoded);
            }
        }

        return $overrides;
    }
}
