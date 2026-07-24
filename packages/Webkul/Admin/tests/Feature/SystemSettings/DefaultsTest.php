<?php

beforeEach(fn () => $this->loginAsAdmin());

it('ships appearance, email and debug as default system settings rows', function () {
    $this->get(route('admin.settings.system.index'))
        ->assertOk()
        ->assertSee(trans('admin::app.settings.appearance.title'))
        ->assertSee(trans('admin::app.settings.system-settings.email.title'))
        ->assertSee(trans('admin::app.settings.system-settings.debug.title'));

    // Appearance links to its own page; email/debug to the generic edit page.
    $this->get(route('admin.settings.system.index'))
        ->assertSee(route('admin.settings.appearance.index'))
        ->assertSee(route('admin.settings.system.edit', 'system.email'));
});

it('persists the debug row to its original core-config key (no code relocation)', function () {
    // Email/Debug reference their existing config('core') groups, so saving from the
    // hub writes the same codes the app already reads — never system.debug.*.
    $this->put(route('admin.settings.system.update', 'system.debug'), [
        'general' => ['debug' => ['settings' => ['enabled' => '1']]],
    ])->assertRedirect(route('admin.settings.system.edit', 'system.debug'));

    // getConfigData memoises per request in request()->attributes; the debug value read
    // during the PUT cached a pre-write miss, so drop it before re-reading.
    collect(request()->attributes->keys())
        ->filter(fn (string $key): bool => str_starts_with($key, 'core_config_memo.'))
        ->each(fn (string $key) => request()->attributes->remove($key));

    expect(core()->getConfigData('general.debug.settings.enabled'))->toBe('1');
});
