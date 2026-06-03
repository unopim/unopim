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

/**
 * Every state-changing installer endpoint must refuse to run on a live
 * instance, so re-installation / re-seeding cannot be triggered after setup.
 */
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

    it('forbids seed-sample-data once installed', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/seed-sample-data')
            ->assertForbidden();
    });
});

/**
 * The completion marker must be written at the true end of the UI flow so the
 * installer seals itself, while still allowing the optional demo-data step
 * that legitimately runs after the admin is created.
 */
describe('Installer completion marker', function () {
    beforeEach(function () {
        if (file_exists($this->marker)) {
            unlink($this->marker);
        }
    });

    it('seals the installer after admin setup when no demo data is requested', function () {
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

    it('defers sealing past admin setup when demo data is requested', function () {
        $this->withoutMiddleware()
            ->postJson('/install/api/admin-config-setup', [
                'admin'            => 'Real Admin',
                'email'            => 'realadmin@example.com',
                'password'         => 'secret123',
                'timezone'         => 'UTC',
                'locale'           => 'en_US',
                'seed_sample_data' => true,
            ])
            ->assertSuccessful();

        // Not sealed yet — the demo-data step still needs to run.
        expect(file_exists($this->marker))->toBeFalse();
    });
});
