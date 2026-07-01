<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

beforeEach(function () {

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    $this->marker = tempnam(sys_get_temp_dir(), 'unopim_installed_');

    expect($this->marker)->not->toBeFalse();

    unlink($this->marker);

    config(['installer.installed_marker' => $this->marker]);

    DB::table('core_config')->where('code', 'installer.installed')->delete();
});

afterEach(function () {
    if (file_exists($this->marker)) {
        unlink($this->marker);
    }

    DB::table('core_config')->where('code', 'installer.installed')->delete();
});

describe('Installer pre-auth admin takeover', function () {
    it('seals the installer routes against the AJAX-header bypass once installed', function () {
        file_put_contents($this->marker, 'installed');

        $this->postJson('/install/api/admin-config-setup', [
            'admin'    => 'Unauthorized Setup',
            'email'    => 'unauthorized@example.test',
            'password' => 'unauthorized-pass',
            'timezone' => 'UTC',
            'locale'   => 'en_US',
        ])->assertRedirect();

        $this->assertDatabaseMissing('admins', ['email' => 'unauthorized@example.test']);
    });

    it('denies admin-config-setup at the controller even if middleware is bypassed', function () {
        file_put_contents($this->marker, 'installed');

        $original = DB::table('admins')->where('id', 1)->first();

        $this->withoutMiddleware()
            ->postJson('/install/api/admin-config-setup', [
                'admin'    => 'Unauthorized Setup',
                'email'    => 'unauthorized@example.test',
                'password' => 'unauthorized-pass',
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

describe('Installer endpoints sealed once installed', function () {
    beforeEach(function () {
        file_put_contents($this->marker, 'installed');
    });

    it('forbids env-file-setup once installed', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/env-file-setup', ['db_prefix' => 'ab'])
            ->assertForbidden();
    });

    it('forbids run-migration once installed', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/run-migration')
            ->assertForbidden();
    });

    it('forbids run-seeder once installed', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/run-seeder')
            ->assertForbidden();
    });
});

describe('Installer completion marker', function () {
    beforeEach(function () {
        if (file_exists($this->marker)) {
            unlink($this->marker);
        }
    });

    it('seals the installer after admin setup', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/admin-config-setup', [
                'admin'    => 'Real Admin',
                'email'    => 'realadmin@example.com',
                'password' => 'secret123',
                'timezone' => 'UTC',
                'locale'   => 'en_US',
            ])
            ->assertSuccessful();

        expect(file_exists($this->marker))->toBeTrue();
    });
});
