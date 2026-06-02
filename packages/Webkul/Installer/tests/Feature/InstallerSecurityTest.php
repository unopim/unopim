<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

beforeEach(function () {

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;
});

afterEach(function () {
    if ($this->markerExisted) {
        file_put_contents($this->marker, $this->markerContents);
    } elseif (file_exists($this->marker)) {
        unlink($this->marker);
    }
});

/**
 * Pre-authentication administrative takeover via the installer.
 *
 * An unauthenticated attacker used to overwrite admin id 1 by POSTing to
 * `install/api/admin-config-setup` with an `X-Requested-With: XMLHttpRequest`
 * header (bypassing the CanInstall redirect). These tests lock both layers:
 * the CanInstall middleware seal and the controller defence-in-depth guard.
 */
describe('Installer pre-auth admin takeover', function () {
    it('seals the installer routes against the AJAX-header bypass once installed', function () {
        file_put_contents($this->marker, 'installed');

        $this->postJson('/install/api/admin-config-setup', [
            'admin'    => 'Hacker',
            'email'    => 'attacker@evil.com',
            'password' => 'pwned123',
            'timezone' => 'UTC',
            'locale'   => 'en_US',
        ])->assertRedirect();

        $this->assertDatabaseMissing('admins', ['email' => 'attacker@evil.com']);
    });

    it('denies admin-config-setup at the controller even if middleware is bypassed', function () {
        file_put_contents($this->marker, 'installed');

        $original = DB::table('admins')->where('id', 1)->first();

        $this->withoutMiddleware()
            ->postJson('/install/api/admin-config-setup', [
                'admin'    => 'Hacker',
                'email'    => 'attacker@evil.com',
                'password' => 'pwned123',
                'timezone' => 'UTC',
                'locale'   => 'en_US',
            ])
            ->assertForbidden();

        $after = DB::table('admins')->where('id', 1)->first();

        expect($after->email)->toBe($original->email);
        expect($after->password)->toBe($original->password);
    });

    it('still allows admin-config-setup while the install is in progress', function () {

        if (file_exists($this->marker)) {
            unlink($this->marker);
        }

        $this->withoutMiddleware()
            ->postJson('/install/api/admin-config-setup', [
                'admin'    => 'Real Admin',
                'email'    => 'realadmin@example.com',
                'password' => 'secret123',
                'timezone' => 'UTC',
                'locale'   => 'en_US',
            ])
            ->assertSuccessful();

        $this->assertDatabaseHas('admins', [
            'id'    => 1,
            'email' => 'realadmin@example.com',
        ]);
    });
});
