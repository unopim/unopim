<?php

/**
 * Build the shared configuration form payload for one `general.magic_ai` section.
 *
 * @param  array<string, string>  $fields
 * @return array<string, mixed>
 */
function magicAiConfigPayload(string $section, array $fields): array
{
    $configData = collect(include __DIR__.'/../../../../src/Config/system.php')
        ->first(fn ($item) => ($item['key'] ?? '') === 'general.magic_ai.'.$section);

    return [
        'general' => ['magic_ai' => [$section => $fields]],
        'keys'    => array_fill(0, count($fields), json_encode($configData)),
    ];
}

it('points the magic ai settings form at its own save route', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.magic_ai.settings.index'))
        ->assertOk()
        ->assertSee(route('admin.magic_ai.settings.store'));
});

it('saves the agentic pim section for a role holding only magic ai permissions', function () {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'ai-agent',
        'ai-agent.general',
    ]);

    $payload = magicAiConfigPayload('agentic_pim', [
        'enabled'              => '1',
        'max_steps'            => '5',
        'daily_token_budget'   => '500000',
        'auto_enrichment'      => '1',
        'quality_monitor'      => '1',
        'confidence_threshold' => '0.7',
        'approval_mode'        => 'auto',
    ]);

    $this->post(route('admin.magic_ai.settings.store'), $payload)
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.agentic_pim.enabled',
        'value' => '1',
    ]);
});

it('saves the magic ai settings section for a role holding only magic ai permissions', function () {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'ai-agent',
        'ai-agent.general',
    ]);

    $this->post(route('admin.magic_ai.settings.store'), magicAiConfigPayload('settings', ['enabled' => '1']))
        ->assertRedirect()
        ->assertSessionHas('success', trans('admin::app.configuration.index.save-message'));

    $this->assertDatabaseHas('core_config', [
        'code'  => 'general.magic_ai.settings.enabled',
        'value' => '1',
    ]);
});

it('still refuses the magic ai save for a role without the magic ai permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.magic_ai.settings.store'), magicAiConfigPayload('settings', ['enabled' => '1']))
        ->assertForbidden();
});

it('refuses an unrelated configuration save for a role holding only magic ai permissions', function () {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'ai-agent',
        'ai-agent.general',
    ]);

    $this->post(route('admin.configuration.store', ['general', 'design']), [
        'general' => ['design' => ['admin_logo' => ['favicon' => '']]],
        'keys'    => [],
    ])->assertForbidden();
});

it('keeps the magic ai save scoped to the magic ai group', function () {
    $this->loginWithPermissions(permissions: [
        'dashboard',
        'ai-agent',
        'ai-agent.general',
    ]);

    $payload = magicAiConfigPayload('settings', ['enabled' => '1']);
    $payload['general']['debug'] = ['settings' => ['enabled' => '1']];

    $this->post(route('admin.magic_ai.settings.store'), $payload)
        ->assertRedirect();

    $this->assertDatabaseMissing('core_config', [
        'code' => 'general.debug.settings.enabled',
    ]);
});
