<?php

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Webkul\Admin\Jobs\ProcessMassActionSelection;
use Webkul\User\Tests\Concerns\UserAssertions;
use Webkul\Webhook\Jobs\MassDeleteWebhookLogs;
use Webkul\Webhook\Models\WebhookLog;

uses(UserAssertions::class);

function createWebhookLogs(string $sku, int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $index) => WebhookLog::create([
            'sku'       => $sku.$index,
            'event'     => 'product.updated',
            'user'      => 'tester',
            'status'    => true,
            'http_code' => 200,
        ])->id)
        ->all();
}

it('only queues the selection resolver when all matching logs are selected', function () {
    $this->loginAsAdmin();

    $matching = createWebhookLogs('LOGQ-MATCH-', 3);

    Bus::fake();

    $this->postJson(route('webhook.logs.mass_delete'), [
        'select_all' => true,
        'filters'    => ['sku' => ['LOGQ-MATCH-']],
    ])->assertOk()
        ->assertJsonPath('message', trans('webhook::app.configuration.webhook.logs.index.select-all.delete.queued'));

    Bus::assertBatchCount(1);

    Bus::assertBatched(fn (PendingBatch $batch) => $batch->name === 'webhook::app.configuration.webhook.logs.index.select-all.delete'
        && $batch->jobs->count() === 1
        && $batch->jobs->first() instanceof ProcessMassActionSelection);

    Bus::assertNotDispatched(MassDeleteWebhookLogs::class);

    expect(WebhookLog::whereIn('id', $matching)->count())->toBe(3);
});

it('deletes every log matching the filters and keeps the others', function () {
    $this->loginAsAdmin();

    $matching = createWebhookLogs('LOGDEL-MATCH-', 5);
    $other = createWebhookLogs('LOGDEL-OTHER-', 2);

    $this->postJson(route('webhook.logs.mass_delete'), [
        'select_all' => true,
        'filters'    => ['sku' => ['LOGDEL-MATCH-']],
    ])->assertOk();

    expect(WebhookLog::whereIn('id', $matching)->count())->toBe(0)
        ->and(WebhookLog::whereIn('id', $other)->count())->toBe(2);
});

it('deletes only the listed ids when no select-all flag is sent', function () {
    $this->loginAsAdmin();

    $ids = createWebhookLogs('LOGIDS-', 4);

    $this->postJson(route('webhook.logs.mass_delete'), [
        'indices' => array_slice($ids, 0, 2),
    ])->assertOk()
        ->assertJsonPath('message', trans('webhook::app.configuration.webhook.logs.index.delete-success'));

    expect(WebhookLog::whereIn('id', array_slice($ids, 0, 2))->count())->toBe(0)
        ->and(WebhookLog::whereIn('id', array_slice($ids, 2))->count())->toBe(2);
});

it('forbids mass deletion without the permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $ids = createWebhookLogs('LOGFORBID-', 2);

    $this->postJson(route('webhook.logs.mass_delete'), ['indices' => $ids])->assertForbidden();
    $this->postJson(route('webhook.logs.mass_delete'), ['select_all' => true])->assertForbidden();

    expect(WebhookLog::whereIn('id', $ids)->count())->toBe(2);
});
