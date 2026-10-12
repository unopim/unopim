<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Str;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

beforeEach(function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->admin = $this->loginAsAdmin();

    $this->target = Admin::factory()->create([
        'name'    => 'Valid Name',
        'role_id' => Role::factory()->create(['permission_type' => 'all'])->id,
    ]);
});

describe('the server side is not the source of the duplicate', function () {
    it('renders exactly one error placeholder for the name field on the user edit page', function () {
        $html = $this->get(route('admin.settings.users.edit', $this->target->id))->getContent();

        expect(substr_count($html, 'data-error-slot="name"'))->toBe(1)
            ->and(preg_match_all('/<v-error-message[^>]*\sname="name"/s', $html))->toBe(1);
    });

    it('renders exactly one error placeholder for the name field on the account page', function () {
        $html = $this->get(route('admin.account.edit'))->getContent();

        expect(substr_count($html, 'data-error-slot="name"'))->toBe(1);
    });

    it('returns exactly one message for the rejected name', function () {
        $response = $this->withHeader('X-Ajax-Form', 'true')->put(route('admin.settings.users.update'), [
            'id'           => $this->target->id,
            'name'         => 'demo-756348####',
            'email'        => $this->target->email,
            'role_id'      => $this->target->role_id,
            'ui_locale_id' => $this->target->ui_locale_id ?: 58,
            'timezone'     => 'UTC',
            'password'     => '',
        ]);

        $response->assertUnprocessable();

        expect($response->json('errors.name'))->toHaveCount(1)
            ->and($response->json('errors.name.0'))->toBe('The name can only accept alpha, numeric and spaces.');
    });
});

describe('the client side renders the first rejected field once', function () {
    it('guards the per field notice against a field that already has an error placeholder', function () {
        $appJs = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/assets/js/app.js')
        );

        expect($appJs)->toContain(
            "if (control && ! control.querySelector('[data-error-slot=\"' + CSS.escape(field) + '\"]')) {"
        );
    });

    it('skips the notice in revealInvalidField when the field already has an error placeholder', function () {
        $appJs = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/assets/js/app.js')
        );

        $reveal = Str::between($appJs, 'window.revealInvalidField = (element, message = null) => {', '// Ref-counted body scroll lock');

        expect($reveal)
            ->toContain('if (message && controlGroup && ! controlGroup.querySelector("[data-error-slot]")) {')
            ->toContain('window.markFieldInvalid(controlGroup, message);');
    });

    it('still passes the message through, which the lazily mounted attribute groups depend on', function () {
        $appJs = file_get_contents(
            base_path('packages/Webkul/Admin/src/Resources/assets/js/app.js')
        );

        $revealCall = Str::between($appJs, 'if (firstField) {', '// A custom 422 may carry');

        expect($revealCall)->toContain('errors[firstField],');

        $method = Str::between($appJs, 'revealInvalidField(element, name = null, groupId = null, message = null) {', 'onInvalidSubmit');

        expect($method)->toContain('attribute-group:reveal-field')
            ->toContain('{ name, groupId, message }');
    });
});

describe('the duplicate is not specific to the name field', function () {
    it('makes whichever field sorts first in the error bag the duplicated one', function () {
        $response = $this->withHeader('X-Ajax-Form', 'true')->put(route('admin.settings.users.update'), [
            'id'           => $this->target->id,
            'name'         => 'Valid Name',
            'email'        => 'not-an-email',
            'role_id'      => $this->target->role_id,
            'ui_locale_id' => $this->target->ui_locale_id ?: 58,
            'timezone'     => 'UTC',
            'password'     => '',
        ]);

        $response->assertUnprocessable();

        expect(array_key_first($response->json('errors')))->toBe('email');
    });
});
