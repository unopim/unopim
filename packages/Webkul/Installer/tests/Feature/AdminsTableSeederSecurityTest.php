<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\Installer\Database\Seeders\User\AdminsTableSeeder;

function setEnvVar(string $name, string $value): void
{
    $_SERVER[$name] = $value;
    $_ENV[$name] = $value;
    putenv("{$name}={$value}");
}

function clearEnvVar(string $name): void
{
    unset($_SERVER[$name], $_ENV[$name]);
    putenv($name);
}

beforeEach(function () {
    clearEnvVar('INSTALLER_ADMIN_EMAIL');
    clearEnvVar('INSTALLER_ADMIN_PASSWORD');
    @unlink(storage_path('app/admin-credentials.txt'));

    DB::table('admins')->delete();
});

afterEach(function () {
    clearEnvVar('INSTALLER_ADMIN_EMAIL');
    clearEnvVar('INSTALLER_ADMIN_PASSWORD');
    @unlink(storage_path('app/admin-credentials.txt'));
});

it('never bcrypts the old hardcoded admin123 default when env vars are blank', function () {
    app(AdminsTableSeeder::class)->run();

    $hash = DB::table('admins')->where('email', 'admin@example.com')->value('password');

    expect($hash)->not->toBeNull()
        ->and(Hash::check('admin123', $hash))->toBeFalse();
});

it('generates a 20-char random password and writes a 0600 credentials file when env is blank', function () {
    app(AdminsTableSeeder::class)->run();

    $path = storage_path('app/admin-credentials.txt');
    expect(file_exists($path))->toBeTrue()
        ->and(fileperms($path) & 0777)->toBe(0600);

    preg_match('/password:\s*(\S+)/', file_get_contents($path), $m);
    $random = $m[1] ?? '';

    expect($random)->toHaveLength(20);

    $hash = DB::table('admins')->where('email', 'admin@example.com')->value('password');
    expect(Hash::check($random, $hash))->toBeTrue();
});

it('uses INSTALLER_ADMIN_EMAIL from env when set', function () {
    setEnvVar('INSTALLER_ADMIN_EMAIL', 'ops@yourco.com');

    app(AdminsTableSeeder::class)->run();

    expect(DB::table('admins')->where('email', 'ops@yourco.com')->exists())->toBeTrue()
        ->and(DB::table('admins')->where('email', 'admin@example.com')->exists())->toBeFalse();
});

it('uses INSTALLER_ADMIN_PASSWORD from env', function () {
    setEnvVar('INSTALLER_ADMIN_PASSWORD', 'my-very-secret-pw');

    app(AdminsTableSeeder::class)->run();

    $hash = DB::table('admins')->where('email', 'admin@example.com')->value('password');

    expect(Hash::check('my-very-secret-pw', $hash))->toBeTrue();
});

it('lets $parameters override env vars', function () {
    setEnvVar('INSTALLER_ADMIN_EMAIL', 'env@example.com');
    setEnvVar('INSTALLER_ADMIN_PASSWORD', 'env-pass');

    app(AdminsTableSeeder::class)->run([
        'admin_email'    => 'param@example.com',
        'admin_password' => 'param-pass',
    ]);

    $hash = DB::table('admins')->where('email', 'param@example.com')->value('password');

    expect($hash)->not->toBeNull()
        ->and(Hash::check('param-pass', $hash))->toBeTrue()
        ->and(DB::table('admins')->where('email', 'env@example.com')->exists())->toBeFalse();
});

it('treats a present-but-blank env var as unset and falls back to the default email', function () {
    setEnvVar('INSTALLER_ADMIN_EMAIL', '');
    setEnvVar('INSTALLER_ADMIN_PASSWORD', '');

    app(AdminsTableSeeder::class)->run();

    $admin = DB::table('admins')->where('email', 'admin@example.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->email)->toBe('admin@example.com');
});

it('is idempotent — does not overwrite an existing admin with the same email', function () {
    app(AdminsTableSeeder::class)->run(['admin_password' => 'first-pass']);
    $firstHash = DB::table('admins')->where('email', 'admin@example.com')->value('password');

    app(AdminsTableSeeder::class)->run(['admin_password' => 'second-pass']);
    $secondHash = DB::table('admins')->where('email', 'admin@example.com')->value('password');

    expect($secondHash)->toBe($firstHash)
        ->and(Hash::check('first-pass', $secondHash))->toBeTrue()
        ->and(Hash::check('second-pass', $secondHash))->toBeFalse();
});

it('does not delete other admin rows', function () {
    DB::table('admins')->insert([
        'id'           => 5000,
        'name'         => 'ops-2',
        'email'        => 'ops2@example.test',
        'password'     => bcrypt('ops2-pass'),
        'api_token'    => str_repeat('x', 80),
        'role_id'      => 1,
        'status'       => 1,
        'timezone'     => 'UTC',
        'ui_locale_id' => 58,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    app(AdminsTableSeeder::class)->run();

    expect(DB::table('admins')->where('id', 5000)->exists())->toBeTrue();
});
