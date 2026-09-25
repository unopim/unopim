<?php

use Illuminate\Support\Facades\Event;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Responses\Data\Usage;
use Webkul\AiAgent\Chat\AgentRunner;
use Webkul\AiAgent\Chat\ToolRegistry;
use Webkul\AiAgent\Chat\Tools\VerifyProduct;

beforeEach(function () {
    Event::fake();
});

function mutationToolResult(string $name, array $payload, bool $failed = false): ToolResult
{
    return new ToolResult(
        id: 'call_1',
        name: $name,
        arguments: [],
        result: json_encode($payload),
        failed: $failed,
    );
}

/**
 * @param  ToolResult[]  $toolResults
 * @return array<string, mixed>
 */
function blockingActionResult(array $toolResults): array
{
    $response = (new AgentResponse('invocation', 'reply', new Usage, new Meta))
        ->withToolCallsAndResults(collect(), collect($toolResults));

    $runner = new AgentRunner(app(ToolRegistry::class));
    $result = [];

    (new ReflectionMethod($runner, 'extractActionResults'))->invokeArgs($runner, [$response, &$result]);

    return $result;
}

/**
 * @param  ToolResult[]  $toolResults
 * @return array<string, mixed>
 */
function streamedActionResult(array $toolResults): array
{
    $stream = (object) [
        'events' => array_map(fn (ToolResult $toolResult): object => (object) ['toolResult' => $toolResult], $toolResults),
    ];

    $runner = new AgentRunner(app(ToolRegistry::class));
    $result = [];

    (new ReflectionMethod($runner, 'extractStreamActionResults'))->invokeArgs($runner, [$stream, &$result]);

    return $result;
}

it('does not flag a mutation when only a read-only tool ran', function () {
    $result = blockingActionResult([
        mutationToolResult('verify_product', ['result' => ['sku' => 'SKU-1', 'issues' => ['missing description']]]),
    ]);

    expect($result)->toHaveKey('result')
        ->and($result)->not->toHaveKey('mutated');
});

it('flags a mutation when a write tool changed data', function () {
    $result = blockingActionResult([
        mutationToolResult('verify_product', ['result' => ['sku' => 'SKU-1']]),
        mutationToolResult('update_product', ['result' => ['updated' => 2]]),
    ]);

    expect($result['mutated'] ?? null)->toBeTrue();
});

it('does not flag a mutation when the write tool errored, failed or changed nothing', function (ToolResult $toolResult) {
    expect(blockingActionResult([$toolResult]))->not->toHaveKey('mutated');
})->with([
    'error payload'    => fn () => mutationToolResult('update_product', ['error' => 'Invalid or empty changes JSON.']),
    'error status'     => fn () => mutationToolResult('assign_categories', ['result' => ['status' => 'Error: product not found']]),
    'nothing updated'  => fn () => mutationToolResult('bulk_edit', ['result' => ['updated' => 0]]),
    'tool call failed' => fn () => mutationToolResult('update_product', ['result' => ['updated' => 1]], failed: true),
]);

it('flags a mutation from the registry write flag rather than a fixed tool list', function () {
    config()->set('ai-agent.tools.'.VerifyProduct::class.'.write', true);
    app()->forgetInstance(ToolRegistry::class);

    $result = blockingActionResult([
        mutationToolResult('verify_product', ['result' => ['sku' => 'SKU-1']]),
    ]);

    expect($result['mutated'] ?? null)->toBeTrue();
});

it('applies the same mutation rule to streamed responses', function () {
    expect(streamedActionResult([mutationToolResult('data_quality_report', ['result' => ['score' => 80]])]))
        ->not->toHaveKey('mutated')
        ->and(streamedActionResult([mutationToolResult('manage_associations', ['result' => ['updated' => 1]])])['mutated'] ?? null)
        ->toBeTrue();
});

it('only reloads the edit page after a response that mutated data', function () {
    $widget = file_get_contents(base_path('packages/Webkul/AiAgent/Resources/views/components/chat-widget.blade.php'));

    preg_match('/shouldAutoRefreshAfterAction\(data\) \{(.*?)\n        \},/s', $widget, $matches);

    expect($matches[1] ?? '')->toContain('if (!data.mutated)');
});
