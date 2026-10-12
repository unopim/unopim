<?php

use Webkul\Core\Models\Locale;

it('answers the ajax profile save with a redirect so the panel reloads in the new ui locale', function () {
    $admin = $this->loginAsAdmin();

    $locale = Locale::query()->firstOrCreate(['code' => 'fr_FR'], ['status' => 1]);

    $response = $this->putJson(route('admin.account.update'), [
        'name'         => $admin->name,
        'email'        => $admin->email,
        'timezone'     => 'Asia/Kolkata',
        'ui_locale_id' => $locale->id,
    ]);

    $response->assertOk()
        ->assertJsonStructure(['message', 'redirect_url'])
        ->assertJsonFragment(['redirect_url' => route('admin.account.edit')]);

    expect($admin->fresh()->ui_locale_id)->toBe($locale->id);
});

it('serves the very next request in the freshly saved ui locale', function () {
    $admin = $this->loginAsAdmin();

    $locale = Locale::query()->firstOrCreate(['code' => 'fr_FR'], ['status' => 1]);

    $this->putJson(route('admin.account.update'), [
        'name'         => $admin->name,
        'email'        => $admin->email,
        'timezone'     => 'Asia/Kolkata',
        'ui_locale_id' => $locale->id,
    ])->assertOk();

    $this->get(route('admin.account.edit'))->assertOk();

    expect(app()->getLocale())->toBe('fr_FR');
});
