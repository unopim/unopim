<?php

use Webkul\Installer\Helpers\DemoDataInstaller;

/**
 * Stubs DemoDataInstaller so the command can be exercised without a real
 * database. $seedCalled / $forceSeen let a test assert whether (and how)
 * seeding was invoked, which is how we prove the confirmation gate blocks
 * a destructive re-seed when the operator declines.
 */
function fakeDemoInstaller(array $result, bool &$seedCalled, bool &$forceSeen): void
{
    app()->instance(DemoDataInstaller::class, new class($result, $seedCalled, $forceSeen) extends DemoDataInstaller
    {
        public function __construct(
            private array $result,
            private bool &$seedCalled,
            private bool &$forceSeen,
        ) {}

        public function seed(?Closure $reporter = null, bool $force = false): array
        {
            $this->seedCalled = true;
            $this->forceSeen = $force;

            ($reporter ?? static fn () => null)('Seeding demo extras...');
            ($reporter ?? static fn () => null)('Seeding demo categories...');

            return $this->result;
        }
    });
}

describe('unopim:install:demo-data (issue #975 — destructive re-seed guard)', function () {
    it('seeds and exits 0 when --force is passed', function () {
        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsOutputToContain('Seeding demo extras...')
            ->expectsOutputToContain('Seeding demo categories...')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue()
            ->and($forceSeen)->toBeTrue();
    });

    it('exits non-zero and prints the seeder error when seeding fails', function () {
        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => false, 'error' => 'JSON missing'], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsOutputToContain('Failed to seed sample data: JSON missing')
            ->assertExitCode(1);
    });

    it('warns but proceeds without confirmation in local env', function () {
        app()['env'] = 'local';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Your existing data will be removed')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue()
            ->and($forceSeen)->toBeTrue();
    });

    it('blocks seeding in production without --force', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->assertExitCode(1);

        expect($seedCalled)->toBeFalse();
    });

    it('bypasses the production confirmation with --force', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue();
    });
});
