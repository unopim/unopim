<?php

namespace Webkul\AiAgent\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\AiErrorResolver;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Models\ChatReply;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

/**
 * Generate one chat reply on a queue worker and write its text into the
 * ChatReply row as it streams. This is what lets the reply survive a full page
 * reload: generation is decoupled from the browser connection, and the widget
 * polls the row (and resumes on the next page) instead of holding a live stream.
 */
class ChatReplyJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** A chat generation can take up to ~2 minutes. */
    public int $timeout = 180;

    /** Never retry — a retry would re-run the LLM and double the token cost. */
    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $context  Serialized ChatContext inputs (scalars + ids).
     */
    public function __construct(
        protected string $replyId,
        protected array $context,
    ) {}

    public function handle(AgentRunner $agentRunner): void
    {
        $reply = ChatReply::find($this->replyId);

        if (! $reply) {
            return;
        }

        $context = $this->rebuildContext();

        if (! $context) {
            $reply->update([
                'status' => ChatReply::STATUS_ERROR,
                'error'  => 'Chat context could not be rebuilt.',
            ]);

            return;
        }

        // Tools resolve the acting admin from the guard; set it for the worker.
        if ($context->user) {
            auth()->guard('admin')->setUser($context->user);
        }

        $buffer = '';
        $lastWrite = 0.0;

        $emit = function (string $event, array $data) use ($reply, &$buffer, &$lastWrite): void {
            if ($event === 'text_delta') {
                $buffer .= $data['chunk'] ?? '';

                // Throttle DB writes so a fast token stream doesn't hammer the row.
                $now = microtime(true);
                if ($now - $lastWrite > 0.15) {
                    $reply->update(['content' => $buffer, 'status' => ChatReply::STATUS_GENERATING]);
                    $lastWrite = $now;
                }
            } elseif ($event === 'status') {
                $reply->update(['agent_status' => $data['message'] ?? null, 'status' => ChatReply::STATUS_GENERATING]);
            } elseif ($event === 'tool_call') {
                $reply->update(['agent_status' => $data['tool'] ?? null, 'status' => ChatReply::STATUS_GENERATING]);
            }
        };

        try {
            $result = $agentRunner->generate($context, $emit);

            // The streamed text is $result['reply']; the rest is the action payload
            // (mirrors the SSE 'complete' event, which strips 'reply').
            $final = $result['reply'] ?? $buffer;
            unset($result['reply']);

            $reply->update([
                'content'      => $final,
                'actions'      => $result,
                'agent_status' => null,
                'tokens_used'  => (int) ($result['data']['tokens_used'] ?? 0),
                'status'       => ChatReply::STATUS_DONE,
            ]);
        } catch (\Throwable $e) {
            $resolved = AiErrorResolver::resolve($e);

            if ($resolved['is_known']) {
                Log::warning('AI Agent async chat provider error', ['type' => $e::class, 'message' => $e->getMessage()]);
            } else {
                Log::error('AI Agent async chat error', ['exception' => $e, 'reply' => $this->replyId]);
            }

            $reply->update([
                'status'       => ChatReply::STATUS_ERROR,
                'error'        => $resolved['message'],
                'agent_status' => null,
            ]);
        } finally {
            auth()->guard('admin')->forgetUser();
        }
    }

    /**
     * Called by the queue when the job fails outright (e.g. timeout). Make sure
     * the widget stops polling instead of spinning forever.
     */
    public function failed(\Throwable $e): void
    {
        ChatReply::where('id', $this->replyId)->update([
            'status'       => ChatReply::STATUS_ERROR,
            'error'        => AiErrorResolver::resolve($e)['message'],
            'agent_status' => null,
        ]);
    }

    protected function rebuildContext(): ?ChatContext
    {
        $c = $this->context;

        $platform = MagicAIPlatform::find($c['platform_id'] ?? null);

        if (! $platform) {
            return null;
        }

        return new ChatContext(
            message: (string) ($c['message'] ?? ''),
            history: $c['history'] ?? [],
            productId: $c['productId'] ?? null,
            productSku: $c['productSku'] ?? null,
            productName: $c['productName'] ?? null,
            locale: (string) ($c['locale'] ?? 'en_US'),
            channel: (string) ($c['channel'] ?? 'default'),
            platform: $platform,
            model: (string) ($c['model'] ?? ''),
            uploadedImagePaths: $c['uploadedImagePaths'] ?? [],
            uploadedFilePaths: $c['uploadedFilePaths'] ?? [],
            currentPage: $c['currentPage'] ?? null,
            user: isset($c['admin_id']) ? Admin::find($c['admin_id']) : null,
        );
    }
}
