<?php

use Webkul\Installer\Helpers\DemoDataInstaller;

/**
 * Stub DemoDataInstaller so the command can be tested without a database.
 */
function fakeDemoInstaller(array $result, bool &$seedCalled, bool &$forceSeen, bool $alreadySeeded = false): void
{
    app()->instance(DemoDataInstaller::class, new class($result, $seedCalled, $forceSeen, $alreadySeeded) extends DemoDataInstaller
    {
        public function __construct(
            private array $result,
            private bool &$seedCalled,
            private bool &$forceSeen,
            private bool $alreadySeeded,
        ) {}

        public function isAlreadySeeded(): bool
        {
            return $this->alreadySeeded;
        }

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
    it('warns and seeds without a prompt in a non-production env', function () {
        app()['env'] = 'local';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('This deletes existing products')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue()
            ->and($forceSeen)->toBeFalse();
    });

    it('exits non-zero and prints the seeder error when seeding fails', function () {
        app()['env'] = 'local';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => false, 'error' => 'JSON missing'], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Failed to seed sample data: JSON missing')
            ->assertExitCode(1);
    });

    it('shows the already-seeded message and skips when data exists and --force is absent', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen, alreadySeeded: true);

        $this->artisan('unopim:install:demo-data')
            ->expectsOutputToContain('Demo data is already seeded')
            ->assertExitCode(0);

        expect($seedCalled)->toBeFalse();
    });

    it('re-seeds already-seeded data with --force in a non-production env', function () {
        app()['env'] = 'local';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen, alreadySeeded: true);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsOutputToContain('This deletes existing products')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue()
            ->and($forceSeen)->toBeTrue();
    });

    it('aborts in production when the operator declines the confirmation', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->expectsOutputToContain('Command cancelled.')
            ->assertExitCode(1);

        expect($seedCalled)->toBeFalse();
    });

    it('proceeds in production when the operator confirms', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen);

        $this->artisan('unopim:install:demo-data')
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue();
    });

    it('still requires confirmation in production even with --force, and aborts on decline', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen, alreadySeeded: true);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsConfirmation('Are you sure you want to run this command?', 'no')
            ->expectsOutputToContain('Command cancelled.')
            ->assertExitCode(1);

        expect($seedCalled)->toBeFalse();
    });

    it('re-seeds in production with --force once the operator confirms', function () {
        app()['env'] = 'production';

        $seedCalled = false;
        $forceSeen = false;
        fakeDemoInstaller(['success' => true], $seedCalled, $forceSeen, alreadySeeded: true);

        $this->artisan('unopim:install:demo-data', ['--force' => true])
            ->expectsConfirmation('Are you sure you want to run this command?', 'yes')
            ->expectsOutputToContain('Sample products seeded successfully.')
            ->assertExitCode(0);

        expect($seedCalled)->toBeTrue()
            ->and($forceSeen)->toBeTrue();
    });
});
