<?php

use Illuminate\Console\Attributes\Signature;
use Webkul\Installer\Console\Commands\Installer;

/**
 * The `unopim:install` command can optionally pull in official open-source
 * add-on packages (DAM, Shopify, Bagisto connectors) and always advertises
 * UnoPim cloud hosting at the end. These tests drive the new package-resolution
 * and banner methods in isolation by overriding handle(), so the heavy
 * migrate:fresh + seed pipeline never runs.
 */
it('resolves valid --with-packages keys', function () {
    $this->app->extend(Installer::class, fn () => new #[Signature('unopim:install {--skip-env-check} {--skip-admin-creation} {--with-demo-data} {--with-packages=}')] class extends Installer
    {
        public function handle(): void
        {
            $this->line('RESOLVED:'.implode(',', $this->resolveSelectedPackages()));

        }
    });

    $this->artisan('unopim:install', [
        '--with-packages'       => 'dam,shopify',
        '--skip-admin-creation' => true,
    ])
        ->expectsOutputToContain('RESOLVED:dam,shopify')
        ->assertExitCode(0);
});

it('skips unknown packages passed to --with-packages', function () {
    $this->app->extend(Installer::class, fn () => new #[Signature('unopim:install {--skip-env-check} {--skip-admin-creation} {--with-demo-data} {--with-packages=}')] class extends Installer
    {
        public function handle(): void
        {
            $this->line('RESOLVED:'.implode(',', $this->resolveSelectedPackages()));

        }
    });

    $this->artisan('unopim:install', [
        '--with-packages'       => 'dam,nope',
        '--skip-admin-creation' => true,
    ])
        ->expectsOutputToContain('Skipping unknown package: nope')
        ->expectsOutputToContain('RESOLVED:dam')
        ->assertExitCode(0);
});

it('installs nothing when no packages are selected', function () {
    $this->app->extend(Installer::class, fn () => new #[Signature('unopim:install {--skip-env-check} {--skip-admin-creation} {--with-demo-data} {--with-packages=}')] class extends Installer
    {
        public function handle(): void
        {
            $this->installOptionalPackages([]);

            $this->line('DONE');

        }
    });

    $this->artisan('unopim:install', [
        '--with-packages'       => '',
        '--skip-admin-creation' => true,
    ])
        ->expectsOutputToContain('DONE')
        ->assertExitCode(0);
});

it('renders the cloud hosting banner with the promo url', function () {
    $this->app->extend(Installer::class, fn () => new #[Signature('unopim:install {--skip-env-check} {--skip-admin-creation} {--with-demo-data} {--with-packages=}')] class extends Installer
    {
        public function handle(): void
        {
            $this->renderCloudHostingBanner();

        }
    });

    $this->artisan('unopim:install', [
        '--skip-admin-creation' => true,
    ])
        ->expectsOutputToContain('https://unopim.com/cloud-hosting/')
        ->assertExitCode(0);
});
