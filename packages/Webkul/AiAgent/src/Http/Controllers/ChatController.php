<?php

namespace Webkul\AiAgent\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\PrismErrorResolver;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Support\ModelRecommender;

/**
 * Handles AI chat messages from the global floating widget.
 *
 * Uses the AgentRunner (Prism-based tool calling) to let the AI
 * autonomously decide which PIM operations to perform. The controller
 * is intentionally thin — all intelligence is in the tools.
 */
class ChatController extends Controller
{
    public function __construct(
        protected AgentRunner $agentRunner,
        protected MagicAIPlatformRepository $platformRepository,
    ) {}

    /**
     * Handle a chat message (text and/or images/files) — blocking JSON response.
     */
    public function send(Request $request): JsonResponse
    {
        $chatContext = $this->buildChatContext($request);

        if ($chatContext instanceof JsonResponse) {
            return $chatContext;
        }

        // Release session lock before the blocking LLM call so other
        // admin requests (product edits, navigation) aren't blocked.
        if (session()->isStarted()) {
            session()->save();
        }

        try {
            $result = $this->agentRunner->run($chatContext);

            // Track token usage for budget enforcement
            $this->recordTokenUsage($chatContext, $result['data']['tokens_used'] ?? 0);

            return new JsonResponse($result);
        } catch (\Throwable $e) {
            $resolved = PrismErrorResolver::resolve($e);

            if ($resolved['is_known']) {
                Log::warning('AI Agent chat provider error', [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            } else {
                Log::error('AI Agent chat error', ['exception' => $e]);
            }

            return new JsonResponse([
                'reply'  => $resolved['message'],
                'action' => 'error',
            ], $resolved['is_known'] ? $resolved['status'] : 422);
        }
    }

    /**
     * Handle a chat message with Server-Sent Events streaming.
     *
     * Returns real-time progress: tool-call indicators, text chunks, and final result.
     */
    public function stream(Request $request): StreamedResponse|JsonResponse
    {
        $chatContext = $this->buildChatContext($request);

        if ($chatContext instanceof JsonResponse) {
            return $chatContext;
        }

        return $this->agentRunner->runStreaming($chatContext);
    }

    /**
     * Build ChatContext from request, or return error JsonResponse.
     */
    protected function buildChatContext(Request $request): ChatContext|JsonResponse
    {
        // Decode history from JSON string when sent via FormData
        if (is_string($request->input('history'))) {
            $request->merge(['history' => json_decode($request->input('history'), true) ?: []]);
        }

        $request->validate([
            'message'     => 'required_without_all:images,files|nullable|string|max:50000',
            'images'      => 'nullable|array|max:5',
            'images.*'    => 'image|mimes:jpeg,png,webp,gif|max:10240',
            'files'       => 'nullable|array|max:3',
            'files.*'     => ['file', 'max:20480', function (string $attribute, $value, $fail) {
                $allowed = ['csv', 'xlsx', 'xls'];
                $ext = strtolower($value->getClientOriginalExtension());
                if (! in_array($ext, $allowed, true)) {
                    $fail(trans('ai-agent::app.common.invalid-file-type', ['types' => implode(', ', $allowed)]));
                }
            }],
            'platform_id' => 'nullable|integer',
            'model'       => 'nullable|string|max:200',
            'context'     => 'nullable|array',
            'history'     => 'nullable|array',
        ]);

        // Check if Agentic PIM is enabled
        $agenticEnabled = core()->getConfigData('general.magic_ai.agentic_pim.enabled');

        if (! $agenticEnabled) {
            return new JsonResponse([
                'reply'  => trans('ai-agent::app.common.chat-disabled'),
                'action' => 'error',
            ], 422);
        }

        // Check daily token budget
        $budgetError = $this->checkTokenBudget();

        if ($budgetError) {
            return $budgetError;
        }

        // Resolve AI platform
        $platform = $this->resolvePlatform(
            (int) $request->input('platform_id', 0),
        );

        if (! $platform) {
            return new JsonResponse([
                'reply'  => trans('ai-agent::app.common.no-platform'),
                'action' => 'error',
            ], 422);
        }

        // When the widget doesn't pass an explicit model, pick a text-capable
        // one from the platform's list. The previous `model_list[0]` fallback
        // would select whichever model sorted first, so providers like OpenAI
        // that expose image-only entries (e.g. chatgpt-image-latest, dall-e-*)
        // could land on a model Prism::text() cannot call.
        $model = (string) $request->input('model', '')
            ?: (ModelRecommender::pickTextModel($platform->model_list ?? []) ?? 'gpt-4o');

        // Store uploaded images — persist across conversation turns via session.
        // The image is uploaded in the first message, but the user may confirm
        // in a follow-up message ("Yes, proceed") which has no image attached.
        $imagePaths = [];
        foreach ($request->file('images', []) as $image) {
            $stored = $image->store('ai-agent/images', 'public');
            $imagePaths[] = storage_path('app/public/'.$stored);
        }

        if (! empty($imagePaths)) {
            // New images uploaded — save to session
            session(['ai_agent_image_paths' => $imagePaths]);
            session(['ai_agent_image_uploaded_at' => now()->timestamp]);
        } else {
            // No new images — restore from session if still fresh (< 10 minutes)
            $uploadedAt = session('ai_agent_image_uploaded_at', 0);
            $isFresh = (now()->timestamp - $uploadedAt) < 600;

            if ($isFresh) {
                $sessionImages = session('ai_agent_image_paths', []);
                $imagePaths = array_filter($sessionImages, fn ($p) => file_exists($p));
            }
        }

        // Store uploaded files — same session persistence pattern.
        //
        // We deliberately use storeAs() with the original filename's
        // extension instead of Laravel's default store() / hashName(), which
        // guesses the extension from the MIME type. PHP often reports CSV
        // uploads as "text/plain", so hashName() would save "products.csv"
        // as "<hash>.txt" — and the ImportProducts tool would then reject
        // it as an unsupported format because the extension check uses
        // pathinfo() on the stored path.
        $filePaths = [];
        foreach ($request->file('files', []) as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            $filename = Str::random(40).($ext !== '' ? '.'.$ext : '');
            $stored = $file->storeAs('ai-agent/files', $filename, 'public');
            $filePaths[] = storage_path('app/public/'.$stored);
        }

        if (! empty($filePaths)) {
            session(['ai_agent_file_paths' => $filePaths]);
            session(['ai_agent_file_uploaded_at' => now()->timestamp]);
        } elseif (empty($filePaths)) {
            $uploadedAt = session('ai_agent_file_uploaded_at', 0);

            if ((now()->timestamp - $uploadedAt) < 600) {
                $sessionFiles = session('ai_agent_file_paths', []);
                $filePaths = array_filter($sessionFiles, fn ($p) => file_exists($p));
            }
        }

        // Build context
        $context = $request->input('context', []);
        $message = $request->input('message', '');

        // If no message but files/images attached, provide a default instruction
        if (empty($message) && (! empty($imagePaths) || ! empty($filePaths))) {
            $message = trans('ai-agent::app.common.process-attached-files');
        }

        return new ChatContext(
            message: $message,
            history: $request->input('history', []),
            productId: ! empty($context['product_id']) ? (int) $context['product_id'] : null,
            productSku: $context['product_sku'] ?? null,
            productName: $context['product_name'] ?? null,
            locale: app()->getLocale() ?: 'en_US',
            channel: 'default',
            platform: $platform,
            model: $model,
            uploadedImagePaths: $imagePaths,
            uploadedFilePaths: $filePaths,
            currentPage: $context['current_page'] ?? null,
            user: auth()->guard('admin')->user(),
        );
    }

    /**
     * Check if the daily token budget has been exceeded.
     */
    protected function checkTokenBudget(): ?JsonResponse
    {
        $configValue = core()->getConfigData('general.magic_ai.agentic_pim.daily_token_budget');

        // Default to 0 (unlimited) if not explicitly configured
        // The config default_value is a form hint only — runtime may return null or empty
        $budget = (int) ($configValue ?: 0);

        if ($budget <= 0) {
            return null; // 0 or unset = unlimited
        }

        $todayUsage = (int) DB::table('ai_agent_token_usage')
            ->where('usage_date', now()->toDateString())
            ->sum('tokens_used');

        if ($todayUsage >= $budget) {
            return new JsonResponse([
                'reply'  => trans('ai-agent::app.common.token-budget-exhausted', ['used' => $todayUsage, 'budget' => $budget]),
                'action' => 'error',
            ], 429);
        }

        return null;
    }

    /**
     * Record token usage for budget tracking.
     */
    protected function recordTokenUsage(ChatContext $context, int $tokensUsed): void
    {
        if ($tokensUsed <= 0) {
            return;
        }

        DB::transaction(function () use ($context, $tokensUsed) {
            $row = DB::table('ai_agent_token_usage')
                ->where('user_id', $context->user?->id)
                ->where('usage_date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            if ($row) {
                DB::table('ai_agent_token_usage')->where('id', $row->id)->update([
                    'tokens_used'   => $row->tokens_used + $tokensUsed,
                    'request_count' => $row->request_count + 1,
                    'updated_at'    => now(),
                ]);
            } else {
                DB::table('ai_agent_token_usage')->insert([
                    'user_id'       => $context->user?->id,
                    'usage_date'    => now()->toDateString(),
                    'tokens_used'   => $tokensUsed,
                    'request_count' => 1,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        });
    }

    /**
     * Resolve the AI platform to use.
     */
    protected function resolvePlatform(int $platformId): ?MagicAIPlatform
    {
        if ($platformId > 0) {
            $platform = $this->platformRepository->find($platformId);

            if ($platform && $platform->status) {
                return $platform;
            }
        }

        // Try default platform
        $platform = $this->platformRepository->getDefault();

        if ($platform) {
            return $platform;
        }

        // Fallback: first active platform
        $activeList = $this->platformRepository->getActiveList();

        return $activeList->first();
    }

    /**
     * Store user feedback (like/dislike) for a chat message.
     */
    public function rate(Request $request): JsonResponse
    {
        $request->validate([
            'rating'  => 'required|in:helpful,not_helpful',
            'message' => 'nullable|string|max:5000',
        ]);

        $user = auth()->guard('admin')->user();
        $rating = $request->input('rating');
        $messageText = $request->input('message', '');
        $ratingLabel = $rating === 'helpful' ? 'positive' : 'negative';

        DB::table('ai_agent_memories')->insert([
            'scope'      => 'catalog',
            'key'        => "message_feedback:{$ratingLabel}",
            'user_id'    => $user?->id,
            'value'      => mb_substr($messageText, 0, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($rating === 'helpful' && ! empty($messageText)) {
            $existing = DB::table('ai_agent_memories')
                ->where('scope', 'catalog')
                ->where('key', 'content_style_preference')
                ->where('user_id', $user?->id)
                ->first();

            $hint = 'User found this response helpful: '.mb_substr($messageText, 0, 200);

            if ($existing) {
                $styleHints = mb_substr($existing->value.'; '.$hint, -500);
                DB::table('ai_agent_memories')->where('id', $existing->id)->update([
                    'value'      => $styleHints,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('ai_agent_memories')->insert([
                    'scope'      => 'catalog',
                    'key'        => 'content_style_preference',
                    'user_id'    => $user?->id,
                    'value'      => $hint,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Return the Magic AI configuration info for the chat widget header.
     */
    public function magicAiConfig(): JsonResponse
    {
        $platform = (string) (core()->getConfigData('general.magic_ai.settings.ai_platform') ?? 'openai');
        $models = (string) (core()->getConfigData('general.magic_ai.settings.api_model') ?? '');
        $enabled = (bool) core()->getConfigData('general.magic_ai.settings.enabled');
        $agenticEnabled = (bool) core()->getConfigData('general.magic_ai.agentic_pim.enabled');

        $modelList = array_values(array_filter(array_map('trim', explode(',', $models))));
        $model = (string) (ModelRecommender::pickTextModel($modelList) ?? '');

        return new JsonResponse([
            'enabled'         => $enabled,
            'agentic_enabled' => $agenticEnabled,
            'platform'        => $platform,
            'model'           => $model ?: ucfirst($platform),
            'label'           => $model ? $model.' ('.ucfirst($platform).')' : ucfirst($platform),
        ]);
    }
}
