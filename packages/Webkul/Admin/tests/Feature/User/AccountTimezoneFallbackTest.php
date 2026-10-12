<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;

const ACCOUNT_CURRENT_PASSWORD = 'Sup3rSecretCurrent';

const ACCOUNT_NEW_PASSWORD = 'Sup3rSecretReplacement';

beforeEach(function () {
    $this->admin = $this->loginAsAdmin(Admin::factory()->create([
        'password' => Hash::make(ACCOUNT_CURRENT_PASSWORD),
    ]));
});

function accountPayload(Admin $admin, string $timezone): array
{
    return [
        'name'                  => $admin->name,
        'email'                 => $admin->email,
        'ui_locale_id'          => $admin->ui_locale_id ?: 58,
        'timezone'              => $timezone,
        'current_password'      => ACCOUNT_CURRENT_PASSWORD,
        'password'              => ACCOUNT_NEW_PASSWORD,
        'password_confirmation' => ACCOUNT_NEW_PASSWORD,
    ];
}

describe('server behaviour when only the timezone is missing', function () {
    it('accepts the submit and keeps the editor existing timezone', function () {
        $this->admin->update(['timezone' => 'Asia/Kolkata']);

        $this->withHeader('X-Ajax-Form', 'true')
            ->put(route('admin.account.update'), accountPayload($this->admin, ''))
            ->assertSuccessful();

        $fresh = $this->admin->fresh();

        expect($fresh->timezone)->toBe('Asia/Kolkata')
            ->and(Hash::check(ACCOUNT_NEW_PASSWORD, $fresh->password))->toBeTrue();
    });

    it('falls back to the configured timezone when the editor has none', function () {
        $this->admin->update(['timezone' => '']);

        $this->withHeader('X-Ajax-Form', 'true')
            ->put(route('admin.account.update'), accountPayload($this->admin, ''))
            ->assertSuccessful();

        expect($this->admin->fresh()->timezone)->toBe(config('app.timezone', 'UTC'));
    });

    it('never echoes the submitted secrets back so the browser has nothing to restore from', function () {
        $response = $this->withHeader('X-Ajax-Form', 'true')
            ->put(route('admin.account.update'), accountPayload($this->admin, ''));

        expect($response->getContent())
            ->not->toContain(ACCOUNT_CURRENT_PASSWORD)
            ->not->toContain(ACCOUNT_NEW_PASSWORD);
    });

    it('saves the very same payload once a timezone is supplied', function () {
        $this->withHeader('X-Ajax-Form', 'true')
            ->put(route('admin.account.update'), accountPayload($this->admin, 'Asia/Kolkata'))
            ->assertSuccessful();

        expect(Hash::check(ACCOUNT_NEW_PASSWORD, $this->admin->fresh()->password))->toBeTrue();
    });
});

describe('why the timezone can no longer fail on its own', function () {
    it('back-fills the account form before the required rule ever sees it', function () {
        $accountForm = file_get_contents(
            base_path('packages/Webkul/Admin/src/Http/Requests/AccountForm.php')
        );

        expect($accountForm)->toContain("'timezone'           => 'required'")
            ->and($accountForm)->toContain('protected function prepareForValidation(): void')
            ->and($accountForm)->toContain("\$this->input('timezone') ?: (\$user?->timezone ?: config('app.timezone', 'UTC'))");

        $column = collect(Schema::getColumns('admins'))->firstWhere('name', 'timezone');

        expect($column['nullable'])->toBeFalse()
            ->and($column['default'])->toContain('UTC');
    });

    it('leaves a cleared ui locale on the editor current one', function () {
        $payload = accountPayload($this->admin, 'Asia/Kolkata');
        $payload['ui_locale_id'] = '';

        $this->withHeader('X-Ajax-Form', 'true')
            ->put(route('admin.account.update'), $payload)
            ->assertSuccessful();

        expect($this->admin->fresh()->ui_locale_id)->toBe($this->admin->ui_locale_id);
    });

    it('still back-fills on user create but not on user update', function () {
        $userForm = file_get_contents(
            base_path('packages/Webkul/Admin/src/Http/Requests/UserForm.php')
        );

        expect($userForm)
            ->toContain("'timezone'           => \$this->input('timezone') ?: config('app.timezone', 'UTC')")
            ->toContain('if ($this->id) {');
    });
});

describe('where the typed current password actually goes', function () {
    it('is wiped by the shared ajax submit handler on every validation failure', function () {
        $appJs = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/assets/js/app.js')
        );

        $catchBlock = Str::between($appJs, '.catch(error => {', 'const response = error.response;');

        expect($catchBlock)
            ->toContain('form.querySelectorAll(\'input[autocomplete="current-password"]\')')
            ->toContain('setFieldValue(input.name, "")');
    });

    it('marks the account current password field with the autocomplete token that handler targets', function () {
        $response = $this->get(route('admin.account.edit'));

        $response->assertSuccessful();
        $response->assertSee('name="current_password"', false);
        $response->assertSee('autocomplete="current-password"', false);
    });

    it('never renders a value attribute on any password input, so no server repopulation is involved', function () {
        $html = $this->get(route('admin.account.edit'))->getContent();

        preg_match_all('/<input[^>]*type="password"[^>]*>/', $html, $matches);

        expect($matches[0])->not->toBeEmpty();

        foreach ($matches[0] as $input) {
            expect($input)->not->toContain('value=');
        }

        $view = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/views/settings/users/edit.blade.php')
        );

        expect(Str::between($view, 'name="current_password"', 'name="password_confirmation"'))
            ->not->toContain('old(');
    });
});
