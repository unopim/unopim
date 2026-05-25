<?php

use Webkul\Installer\Helpers\DemoDataInstaller;

describe('unopim:install:demo-data (issue #794)', function () {
    it('exits 0 and surfaces every step message when seeding succeeds', function () {
        app()->instance(DemoDataInstaller::class, new class extends DemoDataInstaller
        {
            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                ($reporter ?? static fn () => null)('Seeding demo extras...');
                ($reporter ?? static fn () => null)('Seeding demo categories...');

                return ['success' => true];
            }
        });

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Seeding demo extras...')
            ->expectsOutputToContain('Seeding demo categories...')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);
    });

    it('exits non-zero and prints the seeder error when seeding fails', function () {
        app()->instance(DemoDataInstaller::class, new class extends DemoDataInstaller
        {
            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                return ['success' => false, 'error' => 'JSON missing'];
            }
        });

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Failed to seed sample data: JSON missing')
            ->assertExitCode(1);
    });

    it('exits 0 with the "already seeded" message and skips work when demo data is present', function () {
        app()->instance(DemoDataInstaller::class, new class extends DemoDataInstaller
        {
            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                return ['success' => true, 'skipped' => true];
            }
        });

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Demo data is already seeded')
            ->assertExitCode(0);
    });

    it('forwards --force to the installer so a re-seed runs', function () {
        $forceSeen = false;
        app()->instance(DemoDataInstaller::class, new class($forceSeen) extends DemoDataInstaller
        {
            public function __construct(private bool &$forceSeen) {}

            public function seed(?Closure $reporter = null, bool $force = false): array
            {
                $this->forceSeen = $force;

                return ['success' => true];
            }
        });

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->assertExitCode(0);

        expect($forceSeen)->toBeTrue();
    });
});
