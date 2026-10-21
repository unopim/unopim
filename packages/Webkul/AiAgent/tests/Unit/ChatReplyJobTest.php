<?php

use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\AiErrorResolver;
use Webkul\AiAgent\Chat\ChatContext;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Jobs\ChatReplyJob;
use Webkul\AiAgent\Models\ChatReply;
use Webkul\AiAgent\Repositories\ChatReplyRepository;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\User\Models\Admin;

beforeEach(function () {
    MagicAIPlatform::query()->delete();

    $this->platform = MagicAIPlatform::create([
        'label'      => 'OpenAI',
        'provider'   => 'openai',
        'api_key'    => 'sk-test',
        'models'     => 'gpt-4o-mini',
        'status'     => true,
        'is_default' => true,
    ]);

    $this->admin = Admin::factory()->create();
});

/**
 * Bind an AgentRunner whose generate() replays the given events, then returns
 * the result or throws.
 */
function fakeReplyRunner(array $result, ?Throwable $error = null): object
{
    $runner = new class(app(ToolRegistry::class), $result, $error) extends AgentRunner
    {
        public array $contexts = [];

        public ?int $timeout = null;

        public function __construct(ToolRegistry $registry, private array $result, private ?Throwable $error)
        {
            parent::__construct($registry);
        }

        public function generate(ChatContext $context, callable $emit, int $timeout = 120): array
        {
            $this->contexts[] = $context;
            $this->timeout = $timeout;

            $emit('status', ['message' => 'Thinking...']);
            $emit('tool_call', ['step' => 1, 'tool' => 'search_products']);
            $emit('text_delta', ['chunk' => 'Partial']);

            if ($this->error) {
                throw $this->error;
            }

            return $this->result;
        }
    };

    app()->instance(AgentRunner::class, $runner);

    return $runner;
}

function queuedReply(Admin $admin, string $status = ChatReply::STATUS_QUEUED): ChatReply
{
    return app(ChatReplyRepository::class)->create([
        'user_id'         => $admin->id,
        'conversation_id' => 'session_1',
        'status'          => $status,
    ]);
}

function runReplyJob(ChatReply $reply, array $context): void
{
    app()->call([new ChatReplyJob($reply->id, $context), 'handle']);
}

function replyJobContext(MagicAIPlatform $platform, Admin $admin): array
{
    return [
        'message'        => 'Find red shoes',
        'history'        => [['role' => 'user', 'content' => 'Hi']],
        'locale'         => 'en_US',
        'channel'        => 'default',
        'platform_id'    => $platform->id,
        'model'          => 'gpt-4o-mini',
        'admin_id'       => $admin->id,
        'conversationId' => 'session_1',
        'requestId'      => '6f1c1c4e-5d0a-4a43-9a43-3b9b0b0f8e11',
        'extensions'     => ['example' => ['id' => 1]],
    ];
}

it('keeps the job timeout below the queue retry_after', function (int $retryAfter, int $timeout) {
    config(['queue.default' => 'database', 'queue.connections.database.retry_after' => $retryAfter]);

    expect(ChatReplyJob::timeoutFor('database'))->toBe($timeout)
        ->and((new ChatReplyJob('id', []))->timeout)->toBe($timeout)->toBeLessThan($retryAfter);
})->with([
    'default'       => [90, 80],
    'raised'        => [300, 290],
    'very low'      => [20, 10],
]);

it('writes the generated reply and its actions into the row', function () {
    $runner = fakeReplyRunner([
        'reply'       => 'Final reply',
        'action'      => 'agent_response',
        'data'        => ['steps' => 1, 'tokens_used' => 42],
        'product_url' => 'http://localhost/admin/catalog/products/edit/1',
    ]);
    $reply = queuedReply($this->admin);

    runReplyJob($reply, replyJobContext($this->platform, $this->admin));

    $reply->refresh();

    expect($reply->status)->toBe(ChatReply::STATUS_DONE)
        ->and($reply->content)->toBe('Final reply')
        ->and($reply->tokens_used)->toBe(42)
        ->and($reply->agent_status)->toBeNull()
        ->and($reply->actions)->toEqual([
            'action'      => 'agent_response',
            'data'        => ['steps' => 1, 'tokens_used' => 42],
            'product_url' => 'http://localhost/admin/catalog/products/edit/1',
        ])
        ->and($runner->timeout)->toBeLessThan((new ChatReplyJob('id', []))->timeout)
        ->and($runner->contexts[0]->conversationId)->toBe('session_1')
        ->and($runner->contexts[0]->requestId)->toBe('6f1c1c4e-5d0a-4a43-9a43-3b9b0b0f8e11')
        ->and($runner->contexts[0]->extensions)->toBe(['example' => ['id' => 1]])
        ->and($runner->contexts[0]->user->id)->toBe($this->admin->id)
        ->and(auth()->guard('admin')->user())->toBeNull();
});

it('records a failed generation as an error the widget can show', function () {
    fakeReplyRunner([], new RuntimeException('Provider exploded'));
    $reply = queuedReply($this->admin);

    runReplyJob($reply, replyJobContext($this->platform, $this->admin));

    $reply->refresh();

    expect($reply->status)->toBe(ChatReply::STATUS_ERROR)
        ->and($reply->error)->toBe(AiErrorResolver::resolve(new RuntimeException('Provider exploded'))['message'])
        ->and($reply->actions)->toBe(['discard_images' => false])
        ->and($reply->isFinished())->toBeTrue();
});

it('does not generate a reply that was cancelled or already claimed', function (string $status) {
    $runner = fakeReplyRunner(['reply' => 'never', 'action' => 'agent_response', 'data' => []]);
    $reply = queuedReply($this->admin, $status);

    runReplyJob($reply, replyJobContext($this->platform, $this->admin));

    expect($runner->contexts)->toBe([])
        ->and($reply->refresh()->status)->toBe($status);
})->with([ChatReply::STATUS_CANCELLED, ChatReply::STATUS_GENERATING, ChatReply::STATUS_DONE]);

it('fails with a translated message when the chat context cannot be rebuilt', function () {
    $runner = fakeReplyRunner(['reply' => 'never', 'action' => 'agent_response', 'data' => []]);
    $reply = queuedReply($this->admin);

    runReplyJob($reply, ['platform_id' => 0] + replyJobContext($this->platform, $this->admin));

    expect($runner->contexts)->toBe([])
        ->and($reply->refresh()->status)->toBe(ChatReply::STATUS_ERROR)
        ->and($reply->error)->toBe(trans('ai-agent::app.common.reply-context-unavailable'));
});

it('marks only an unfinished reply as failed when the queue gives up on the job', function () {
    $generating = queuedReply($this->admin, ChatReply::STATUS_GENERATING);
    $done = queuedReply($this->admin, ChatReply::STATUS_DONE);

    (new ChatReplyJob($generating->id, []))->failed(new RuntimeException('Timed out'));
    (new ChatReplyJob($done->id, []))->failed(new RuntimeException('Timed out'));

    expect($generating->refresh()->status)->toBe(ChatReply::STATUS_ERROR)
        ->and($generating->error)->not->toBeEmpty()
        ->and($done->refresh()->status)->toBe(ChatReply::STATUS_DONE);
});
