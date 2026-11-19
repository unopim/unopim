<?php

use Webkul\User\Tests\Concerns\UserAssertions;
use Webkul\Webhook\Models\Webhook;

uses(UserAssertions::class);

function createWebhooks(string $prefix, int $count): array
{
    return collect(range(1, $count))
        ->map(fn (int $index) => Webhook::create([
            'name'      => $prefix.$index,
            'url'       => 'https://example.com/hooks/'.$prefix.$index,
            'is_active' => true,
            'events'    => ['product.updated'],
        ])->id)
        ->all();
}

it('deletes every webhook matching the filters when all matching are selected', function () {
    $this->loginAsAdmin();

    $matching = createWebhooks('SELDEL-MATCH-', 4);
    $other = createWebhooks('SELDEL-OTHER-', 2);

    $this->postJson(route('webhook.mass_delete'), [
        'select_all' => true,
        'filters'    => ['name' => ['SELDEL-MATCH-']],
    ])->assertOk();

    expect(Webhook::whereIn('id', $matching)->count())->toBe(0)
        ->and(Webhook::whereIn('id', $other)->count())->toBe(2);
});

it('deletes only the given webhooks when an id list is sent', function () {
    $this->loginAsAdmin();

    [$first, $second, $third] = createWebhooks('SELIDX-', 3);

    $this->postJson(route('webhook.mass_delete'), ['indices' => [$first, $second]])->assertOk();

    expect(Webhook::whereIn('id', [$first, $second])->count())->toBe(0)
        ->and(Webhook::whereKey($third)->exists())->toBeTrue();
});

it('still requires an id list when all matching are not selected', function () {
    $this->loginAsAdmin();

    $this->postJson(route('webhook.mass_delete'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('indices');
});
