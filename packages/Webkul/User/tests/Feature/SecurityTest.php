<?php

use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

use function Pest\Laravel\post;

/*
|--------------------------------------------------------------------------
| Security Vulnerability Tests
|--------------------------------------------------------------------------
|
| These tests verify fixes for 5 security vulnerabilities found in the
| security audit.
|
*/

// ─── Vulnerability 1: Open Redirect via Referer Header ───────────────

it('should not allow open redirect via spoofed referer header on login page', function () {
    $response = $this->withHeaders([
        'Referer' => 'https://attacker.com/admin',
    ])->get(route('admin.session.create'));

    $response->assertStatus(200);

    $intendedUrl = session('url.intended');

    expect($intendedUrl)->not->toContain('attacker.com');
    expect($intendedUrl)->toContain(config('app.url'));
});

it('should not allow open redirect via spoofed referer header on forgot password page', function () {
    $response = $this->withHeaders([
        'Referer' => 'https://evil.com/admin/phishing',
    ])->get(route('admin.forget_password.create'));

    $response->assertStatus(200);

    $intendedUrl = session('url.intended');

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

    $role = Role::first();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Weak Password User',
        'email'                 => 'weakpwd@example.com',
        'password'              => 'a',
        'password_confirmation' => 'a',
        'role_id'               => $role->id,
        'ui_locale_id'          => 1,
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

    $role = Role::first();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Short Password User',
        'email'                 => 'shortpwd@example.com',
        'password'              => '12345',
        'password_confirmation' => '12345',
        'role_id'               => $role->id,
        'ui_locale_id'          => 1,
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
    $this->withoutMiddleware(ThrottleRequests::class);

    $admin = Admin::factory()->create([
        'email'    => 'existing-user@example.com',
        'password' => Hash::make('password'),
    ]);

    $responseExisting = $this->post(route('admin.forget_password.store'), [
        'email' => 'existing-user@example.com',
    ]);

    $responseNonExisting = $this->post(route('admin.forget_password.store'), [
        'email' => 'nonexistent-user-xyz@example.com',
    ]);

    $responseExisting->assertRedirect();
    $responseNonExisting->assertRedirect();

    $responseNonExisting->assertSessionHasNoErrors();
});

// ─── Vulnerability 5: Privilege Escalation via User Edit (HIGH) ──────

it('should not allow a limited user to escalate their own role via user edit', function () {
    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

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

    $limitedAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $customRole->id,
    ]);
    $this->actingAs($limitedAdmin, 'admin');

    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $limitedAdmin->id,
        'name'         => $limitedAdmin->name,
        'email'        => $limitedAdmin->email,
        'role_id'      => $allAccessRole->id,
        'ui_locale_id' => 1,
        'timezone'     => 'UTC',
        'password'     => '',
    ]);

    $limitedAdmin->refresh();

    expect($limitedAdmin->role_id)->toBe($customRole->id)
        ->and($limitedAdmin->role_id)->not->toBe($allAccessRole->id);
});

it('should not allow a limited user to create a new user with all-access role', function () {
    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

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

    $limitedAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $customRole->id,
    ]);
    $this->actingAs($limitedAdmin, 'admin');

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'Escalated User',
        'email'                 => 'escalated@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $allAccessRole->id,
        'ui_locale_id'          => 1,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    $response->assertStatus(403);

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'email' => 'escalated@example.com',
    ]);
});

it('should block user update from user without user management permissions (Burp replay)', function () {
    $catalogueRole = Role::factory()->create([
        'permission_type' => 'custom',
        'permissions'     => [
            'catalog',
            'catalog.products',
            'catalog.products.create',
            'catalog.products.edit',
            'catalog.products.delete',
        ],
    ]);

    $attackerAdmin = Admin::factory()->create([
        'password' => Hash::make('password'),
        'role_id'  => $catalogueRole->id,
    ]);
    $this->actingAs($attackerAdmin, 'admin');

    $allAccessRole = Role::where('permission_type', 'all')->first()
        ?? Role::factory()->create(['permission_type' => 'all', 'permissions' => []]);

    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $attackerAdmin->id,
        'name'         => $attackerAdmin->name,
        'email'        => $attackerAdmin->email,
        'role_id'      => $allAccessRole->id,
        'ui_locale_id' => 1,
        'timezone'     => 'UTC',
        'password'     => '',
    ]);

    $response->assertStatus(401);

    $attackerAdmin->refresh();
    expect($attackerAdmin->role_id)->toBe($catalogueRole->id);
});

it('should allow a superadmin to assign all-access role', function () {
    $this->loginAsAdmin();

    $allAccessRole = Role::factory()->create([
        'permission_type' => 'all',
        'permissions'     => [],
    ]);

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'New Superadmin',
        'email'                 => 'newsuperadmin@example.com',
        'password'              => 'password123',
        'password_confirmation' => 'password123',
        'role_id'               => $allAccessRole->id,
        'ui_locale_id'          => 1,
        'timezone'              => 'UTC',
        'status'                => 1,
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'email'   => 'newsuperadmin@example.com',
        'role_id' => $allAccessRole->id,
    ]);
});
