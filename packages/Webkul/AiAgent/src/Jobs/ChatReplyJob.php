<?php

namespace Webkul\AiAgent\Jobs;

use Closure;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\AiErrorResolver;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Models\ChatReply;
use Webkul\AiAgent\Repositories\ChatReplyRepository;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

/**
 * Generate one chat reply on a queue worker and write its text into the
 * ChatReply row as it streams.
 *
 * Generation is decoupled from the browser connection, so the reply survives
 * a full page reload: the widget polls the row and resumes on the next page.
 */
class ChatReplyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Seconds between content writes while text streams, so a fast token
     * stream does not hammer the row.
     */
    protected const WRITE_INTERVAL = 0.15;

    /**
     * Never retry: a retry re-runs the model and doubles the token cost.
     */
    public int $tries = 1;

    /**
     * Mark the job failed (not released) when it times out.
     */
    public bool $failOnTimeout = true;

    /**
     * Seconds the worker may spend on this job; kept below the connection's
     * retry_after, see timeoutFor().
     */
    public int $timeout;

    /**
     * @param  array<string, mixed>  $context  Serialized ChatContext inputs (scalars and ids).
     */
    public function __construct(
        protected string $replyId,
        protected array $context,
    ) {
        $this->timeout = static::timeoutFor(config('queue.default'));
    }

    /**
     * Job timeout for a queue connection, ten seconds below its retry_after.
     *
     * A timeout at or above retry_after lets the queue hand a still-running
     * job to a second worker, which then fails it while the first worker is
     * still writing the reply.
     */
    public static function timeoutFor(?string $connection): int
    {
        $retryAfter = (int) config("queue.connections.{$connection}.retry_after", 90);

        return max(1, $retryAfter - 10);
    }

    /**
     * Generate the reply, unless it was cancelled or claimed by another worker.
     *
     * The acting admin is set on the guard for the tools and cleared afterwards.
     * The provider timeout stays below the job timeout so a slow provider ends
     * in a readable error instead of a killed worker.
     */
    public function handle(AgentRunner $agentRunner, ChatReplyRepository $chatReplyRepository): void
    {
        if (! $chatReplyRepository->claim($this->replyId)) {
            return;
        }

        $reply = $chatReplyRepository->find($this->replyId);
        $context = $this->rebuildContext();

        if (! $context) {
            $reply->update([
                'status' => ChatReply::STATUS_ERROR,
                'error'  => trans('ai-agent::app.common.reply-context-unavailable'),
            ]);

            return;
        }

        if ($context->user) {
            auth()->guard('admin')->setUser($context->user);
        }

        try {
            $result = $agentRunner->generate($context, $this->progressRecorder($reply), max(1, $this->timeout - 5));

            $reply->update([
                'content'      => $result['reply'],
                'actions'      => Arr::except($result, 'reply'),
                'agent_status' => null,
                'tokens_used'  => (int) ($result['data']['tokens_used'] ?? 0),
                'status'       => ChatReply::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            $error = $agentRunner->handleGenerationError($e, $context);

            $reply->update([
                'status'       => ChatReply::STATUS_ERROR,
                'error'        => $error['message'],
                'actions'      => ['discard_images' => $error['discard_images']],
                'agent_status' => null,
            ]);
        } finally {
            auth()->guard('admin')->forgetUser();
        }
    }

    /**
     * Called by the queue when the job fails outright (for example a timeout),
     * so the widget stops polling instead of waiting forever.
     */
    public function failed(\Throwable $e): void
    {
        app(ChatReplyRepository::class)->failUnfinished($this->replyId, AiErrorResolver::resolve($e)['message']);
    }

    /**
     * Build the emitter that writes generation progress into the reply row.
     *
     * Text is written at most every WRITE_INTERVAL seconds; the final text is
     * written by handle(). A status label goes to agent_status as is, a tool
     * call as "tool:<name>" so the widget can show its translated label.
     */
    protected function progressRecorder(ChatReply $reply): Closure
    {
        $buffer = '';
        $lastWrite = 0.0;

        return function (string $event, array $data) use ($reply, &$buffer, &$lastWrite): void {
            if ($event === 'text_delta') {
                $buffer .= $data['chunk'] ?? '';

                if (microtime(true) - $lastWrite >= self::WRITE_INTERVAL) {
                    $lastWrite = microtime(true);
                    $reply->update(['content' => $buffer]);
                }

                return;
            }

            if ($event === 'status') {
                $reply->update(['agent_status' => $data['message'] ?? null]);
            } elseif ($event === 'tool_call') {
                $reply->update(['agent_status' => 'tool:'.($data['tool'] ?? '')]);
            }
        };
    }

    /**
     * Rebuild the chat context from the serialized inputs, or null when the
     * platform no longer exists.
     */
    protected function rebuildContext(): ?ChatContext
    {
        $context = $this->context;
        $platform = MagicAIPlatform::find($context['platform_id'] ?? null);

        if (! $platform) {
            return null;
        }

        return new ChatContext(
            message: (string) ($context['message'] ?? ''),
            history: $context['history'] ?? [],
            productId: $context['productId'] ?? null,
            productSku: $context['productSku'] ?? null,
            productName: $context['productName'] ?? null,
            locale: (string) ($context['locale'] ?? 'en_US'),
            channel: (string) ($context['channel'] ?? 'default'),
            platform: $platform,
            model: (string) ($context['model'] ?? ''),
            uploadedImagePaths: $context['uploadedImagePaths'] ?? [],
            uploadedFilePaths: $context['uploadedFilePaths'] ?? [],
            currentPage: $context['currentPage'] ?? null,
            user: isset($context['admin_id']) ? Admin::find($context['admin_id']) : null,
            conversationId: $context['conversationId'] ?? null,
            requestId: $context['requestId'] ?? null,
            hasNewImages: (bool) ($context['hasNewImages'] ?? false),
            extensions: $context['extensions'] ?? [],
        );
    }
}
