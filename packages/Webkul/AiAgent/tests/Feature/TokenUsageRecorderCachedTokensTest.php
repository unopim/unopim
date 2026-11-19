<?php

use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Webkul\AiAgent\Services\TokenUsageRecorder;
use Webkul\User\Models\Admin;

beforeEach(function () {
    $this->admin = Admin::factory()->create();

    DB::table('ai_agent_token_usage')->where('user_id', $this->admin->id)->delete();
});

function todayUsageRow(int $userId): object
{
    return DB::table('ai_agent_token_usage')
        ->where('user_id', $userId)
        ->where('usage_date', now()->toDateString())
        ->first();
}

it('stores cached tokens on the first request of the day', function () {
    app(TokenUsageRecorder::class)->record($this->admin->id, 500, 320);

    $row = todayUsageRow($this->admin->id);

    expect((int) $row->tokens_used)->toBe(500)
        ->and((int) $row->cached_tokens)->toBe(320)
        ->and((int) $row->request_count)->toBe(1);
});

it('accumulates cached tokens across requests on the same day', function () {
    $recorder = app(TokenUsageRecorder::class);

    $recorder->record($this->admin->id, 500, 320);
    $recorder->record($this->admin->id, 200, 180);

    $row = todayUsageRow($this->admin->id);

    expect((int) $row->tokens_used)->toBe(700)
        ->and((int) $row->cached_tokens)->toBe(500)
        ->and((int) $row->request_count)->toBe(2);
});

it('never stores negative cached tokens', function () {
    app(TokenUsageRecorder::class)->record($this->admin->id, 100, -40);

    expect((int) todayUsageRow($this->admin->id)->cached_tokens)->toBe(0);
});

it('exposes cached tokens in the agent usage analytics', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    DB::table('ai_agent_token_usage')->where('usage_date', '>=', now()->subDays(7)->toDateString())->delete();

    app(TokenUsageRecorder::class)->record($this->admin->id, 900, 600);

    $response = $this->getJson(route('ai-agent.dashboard.analytics'))->assertOk();

    expect($response->json('today.cached_tokens'))->toBe(600)
        ->and($response->json('week.cached_tokens'))->toBe(600)
        ->and((int) $response->json('daily_breakdown.0.cached_tokens'))->toBe(600);
});

it('reports zero cached tokens in analytics when the cached_tokens column is absent', function () {
    $this->actingAs(Admin::factory()->create(), 'admin');

    DB::table('ai_agent_token_usage')->where('usage_date', '>=', now()->subDays(7)->toDateString())->delete();

    app(TokenUsageRecorder::class)->record($this->admin->id, 900, 600);

    $this->mock(TokenUsageRecorder::class, fn (MockInterface $mock) => $mock->shouldReceive('tracksCachedTokens')->andReturnFalse());

    $response = $this->getJson(route('ai-agent.dashboard.analytics'))->assertOk();

    expect($response->json('today.tokens'))->toBe(900)
        ->and($response->json('today.cached_tokens'))->toBe(0)
        ->and($response->json('week.cached_tokens'))->toBe(0)
        ->and((int) $response->json('daily_breakdown.0.cached_tokens'))->toBe(0);
});
