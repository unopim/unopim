<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Security Vulnerability Tests
|--------------------------------------------------------------------------
|
| These tests verify fixes for 5 security vulnerabilities found in the
| security audit. Each test should FAIL before the fix is applied and
| PASS after.
|
*/

// ─── Vulnerability 1: Open Redirect via Referer Header ───────────────

it('should not allow open redirect via spoofed referer header on login page', function () {
    $response = $this->withHeaders([
        'Referer' => 'https://attacker.com/admin',
    ])->get(route('admin.session.create'));

    $response->assertStatus(200);

    $intendedUrl = session('url.intended');

    // The intended URL should NOT be an external URL
    expect($intendedUrl)->not->toContain('attacker.com');
    expect($intendedUrl)->toContain(config('app.url'));
});

it('should not allow open redirect via spoofed referer header on forgot password page', function () {
    $response = $this->withHeaders([
        'Referer' => 'https://evil.com/admin/phishing',
    ])->get(route('admin.forget_password.create'));

    $response->assertStatus(200);

    $intendedUrl = session('url.intended');

    // The intended URL should NOT be an external URL
    expect($intendedUrl)->not->toContain('evil.com');
    expect($intendedUrl)->toContain(config('app.url'));
});

// ─── Vulnerability 2: No Rate Limiting on Login ──────────────────────

it('should rate limit login attempts after too many failures', function () {
    $got429 = false;

    for ($i = 0; $i < 10; $i++) {
        $response = post(route('admin.session.store'), [
            'email'    => 'admin@example.com',
            'password' => 'wrong-password-'.$i,
        ]);

        if ($response->status() === 429) {
            $got429 = true;
            break;
        }
    }

    expect($got429)->toBeTrue('Expected a 429 Too Many Requests response after multiple failed login attempts');
});

it('should rate limit forgot password requests', function () {
    $got429 = false;

    for ($i = 0; $i < 10; $i++) {
        $response = post(route('admin.forget_password.store'), [
            'email' => 'bruteforce@example.com',
        ]);

        if ($response->status() === 429) {
            $got429 = true;
            break;
        }
    }

    expect($got429)->toBeTrue('Expected a 429 Too Many Requests response after multiple forgot password requests');
});

// ─── Vulnerability 3: No Server-Side Password Validation ─────────────

it('should reject weak passwords when creating a user', function () {
    $this->loginAsAdmin();

    $locale = Locale::where('code', 'en_US')->first();
    $role = Role::first();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Weak Password User',
        'email'                 => 'weakpwd@example.com',
        'password'              => 'a',
        'password_confirmation' => 'a',
        'role_id'               => $role->id,
        'ui_locale_id'          => $locale->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    $response->assertSessionHasErrors(['password']);

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'email' => 'weakpwd@example.com',
    ]);
});

it('should reject passwords shorter than 6 characters when creating a user', function () {
    $this->loginAsAdmin();

    $locale = Locale::where('code', 'en_US')->first();
    $role = Role::first();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Short Password User',
        'email'                 => 'shortpwd@example.com',
        'password'              => '12345',
        'password_confirmation' => '12345',
        'role_id'               => $role->id,
        'ui_locale_id'          => $locale->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    $response->assertSessionHasErrors(['password']);

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'email' => 'shortpwd@example.com',
    ]);
});

// ─── Vulnerability 4: User Enumeration via Forgot Password ───────────

it('should not reveal whether an email exists via forgot password response', function () {
    // Disable throttle middleware for this test — we're testing enumeration, not rate limiting
    $this->withoutMiddleware(ThrottleRequests::class);

    // First, create a known admin user
    $admin = Admin::factory()->create([
        'email'    => 'existing-user@example.com',
        'password' => Hash::make('password'),
    ]);

    // Request reset for existing email
    $responseExisting = $this->post(route('admin.forget_password.store'), [
        'email' => 'existing-user@example.com',
    ]);

    // Request reset for non-existing email
    $responseNonExisting = $this->post(route('admin.forget_password.store'), [
        'email' => 'nonexistent-user-xyz@example.com',
    ]);

    // Both should redirect (not show errors for non-existing)
    $responseExisting->assertRedirect();
    $responseNonExisting->assertRedirect();

    // Non-existing email should NOT have validation errors revealing the email doesn't exist
    $responseNonExisting->assertSessionHasNoErrors();
});

// ─── Vulnerability 5: Privilege Escalation via User Edit (HIGH) ──────

it('should not allow a limited user to escalate their own role via user edit', function () {
    // Create an 'all' access role (superadmin)
    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

    // Create a custom role with user management permissions
    $customRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => [
            'settings',
            'settings.users',
            'settings.users.users',
            'settings.users.users.create',
            'settings.users.users.edit',
        ],
    ]);

    // Login as the limited user
    $limitedAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $customRole->id,
    ]);
    $this->actingAs($limitedAdmin, 'admin');

    $locale = Locale::where('code', 'en_US')->first();

    // Attempt to escalate own role to superadmin via the user update endpoint
    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $limitedAdmin->id,
        'name'         => $limitedAdmin->name,
        'email'        => $limitedAdmin->email,
        'role_id'      => $allAccessRole->id,
        'ui_locale_id' => $locale->id,
        'timezone'     => 'UTC',
        'password'     => '',
    ]);

    // Refresh the user from database
    $limitedAdmin->refresh();

    // The role should NOT have been changed to the all-access role
    expect($limitedAdmin->role_id)->toBe($customRole->id)
        ->and($limitedAdmin->role_id)->not->toBe($allAccessRole->id);
});

it('should not allow a limited user to create a new user with all-access role', function () {
    // Create an 'all' access role (superadmin)
    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

    // Create a custom role with user management permissions
    $customRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => [
            'settings',
            'settings.users',
            'settings.users.users',
            'settings.users.users.create',
            'settings.users.users.edit',
        ],
    ]);

    // Login as the limited user
    $limitedAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $customRole->id,
    ]);
    $this->actingAs($limitedAdmin, 'admin');

    $locale = Locale::where('code', 'en_US')->first();

    // Attempt to create a new user with all-access role
    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Escalated User',
        'email'                 => 'escalated@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $allAccessRole->id,
        'ui_locale_id'          => $locale->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    // The user should NOT have been created with the all-access role
    $response->assertStatus(403);

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'email' => 'escalated@example.com',
    ]);
});

it('should block user update from user without user management permissions (Burp replay)', function () {
    // This reproduces the exact PoC: user with only catalogue.products permissions
    // replays a captured Burp request to /admin/settings/users/edit
    $catalogueRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => [
            'catalog',
            'catalog.products',
            'catalog.products.create',
            'catalog.products.edit',
            'catalog.products.delete',
            'catalog.products.mass-update',
            'catalog.products.mass-delete',
        ],
    ]);

    $attackerAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $catalogueRole->id,
    ]);
    $this->actingAs($attackerAdmin, 'admin');

    $locale = Locale::where('code', 'en_US')->first();
    $allAccessRole = Role::where('permission_type', 'all')->first()
        ?? Role::factory()->create(['permission_type' => 'all', 'permissions' => []]);

    // Burp replay: PUT to user update endpoint with escalated role_id
    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $attackerAdmin->id,
        'name'         => $attackerAdmin->name,
        'email'        => $attackerAdmin->email,
        'role_id'      => $allAccessRole->id,
        'ui_locale_id' => $locale->id,
        'timezone'     => 'UTC',
        'password'     => '',
    ]);

    // Should be blocked by Bouncer middleware (401) since user lacks settings.users.users.edit permission
    $response->assertStatus(401);

    // Verify role was NOT changed
    $attackerAdmin->refresh();
    expect($attackerAdmin->role_id)->toBe($catalogueRole->id);
});

it('should allow a superadmin to assign all-access role', function () {
    // Login as superadmin (default loginAsAdmin creates one with all-access role)
    $this->loginAsAdmin();

    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

    $locale = Locale::where('code', 'en_US')->first();

    // Superadmin should be able to create a user with all-access role
    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'New Superadmin',
        'email'                 => 'newsuperadmin@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $allAccessRole->id,
        'ui_locale_id'          => $locale->id,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'email'   => 'newsuperadmin@example.com',
        'role_id' => $allAccessRole->id,
    ]);
});

// ─── Login UX: Email preservation on failed login ───────────────────

it('should preserve email field when login fails with wrong password', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $email = 'admin@example.com';

    $response = post(route('admin.session.store'), [
        'email'    => $email,
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionHasInput('email', $email);
});

it('should not preserve password field when login fails', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    $response = post(route('admin.session.store'), [
        'email'    => 'admin@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('admin.session.create'));
    $response->assertSessionMissing('_old_input.password');
});
