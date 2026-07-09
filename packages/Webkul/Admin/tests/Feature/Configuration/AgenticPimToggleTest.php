<?php

use Illuminate\Support\Facades\Hash;
use Webkul\Core\Models\CoreConfig;
use Webkul\User\Models\Admin;

function setAgenticEnabled(string $value): void
{
    CoreConfig::query()->updateOrCreate(
        [
            'code'         => 'general.magic_ai.agentic_pim.enabled',
            'channel_code' => null,
            'locale_code'  => null,
        ],
        ['value' => $value],
    );
}

function loginAdmin(): Admin
{
    return Admin::factory()->create([
        'email'    => 'agentic-toggle@example.com',
        'password' => Hash::make('password-123'),
        'status'   => 1,
    ]);
}

it('does NOT render the agentic pim panel when the setting is disabled', function () {
    $admin = loginAdmin();

    setAgenticEnabled('0');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard.index'));

    $response->assertOk();
    $response->assertDontSee('v-agenting-pim', false);
});

it('renders the agentic pim panel when the setting is enabled', function () {
    $admin = loginAdmin();

    setAgenticEnabled('1');

    $response = $this->actingAs($admin, 'admin')->get(route('admin.dashboard.index'));

    $response->assertOk();
    $response->assertSee('v-agenting-pim', false);
});
