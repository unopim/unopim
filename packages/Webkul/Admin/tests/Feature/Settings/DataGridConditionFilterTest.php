<?php

use Illuminate\Testing\TestResponse;
use Webkul\User\Models\Admin;

use function Pest\Laravel\withHeaders;

beforeEach(function () {
    $this->loginAsAdmin();
});

function usersGrid(array $query): TestResponse
{
    return withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.settings.users.index').'?'.http_build_query($query));
}

it('applies a contains condition to a grid column', function () {
    Admin::factory()->create(['name' => 'Condition Probe']);
    Admin::factory()->create(['name' => 'Unrelated Person']);

    $response = usersGrid([
        'filters' => ['user_name' => [['operator' => 'has', 'value' => 'Condition Probe']]],
    ]);

    $response->assertOk();

    $names = collect($response->json('records'))->pluck('user_name');

    expect($names)->toContain('Condition Probe')
        ->and($names)->not->toContain('Unrelated Person');
});

it('applies an equals condition to a grid column', function () {
    $admin = Admin::factory()->create(['name' => 'Exact Match Probe']);

    $response = usersGrid([
        'filters' => ['user_name' => [['operator' => 'eq', 'value' => 'Exact Match Probe']]],
    ]);

    $response->assertOk();

    expect(collect($response->json('records'))->pluck('user_name'))->toContain($admin->name);
});

it('ignores a filter naming a column the grid does not expose', function () {
    usersGrid([
        'filters' => ['not_a_column' => [['operator' => 'has', 'value' => 'anything']]],
    ])->assertOk();
});

it('still accepts the plain value format', function () {
    Admin::factory()->create(['name' => 'Plain Format Probe']);

    $response = usersGrid([
        'filters' => ['user_name' => ['Plain Format Probe']],
    ]);

    $response->assertOk();

    expect(collect($response->json('records'))->pluck('user_name'))->toContain('Plain Format Probe');
});
