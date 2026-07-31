<?php

use Illuminate\Support\Facades\Artisan;
use Webkul\Installer\Database\Seeders\Demo\DemoAssociationSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoAttributeSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCategorySeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoCoreSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoFamilySeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoMediaSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoPassportSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoProductSeeder;
use Webkul\Installer\Database\Seeders\Demo\DemoWorkspaceSeeder;
use Webkul\Installer\Helpers\DemoDataInstaller;

/**
 * The seeders in the order DemoDataInstaller must run them: configuration
 * before catalog, catalog before anything that links to it.
 */
function demoSeederOrder(): array
{
    return [
        DemoCoreSeeder::class,
        DemoAttributeSeeder::class,
        DemoFamilySeeder::class,
        DemoCategorySeeder::class,
        DemoMediaSeeder::class,
        DemoProductSeeder::class,
        DemoAssociationSeeder::class,
        DemoPassportSeeder::class,
        DemoWorkspaceSeeder::class,
    ];
}

/**
 * Replace every demo seeder with a spy that records the order it ran in.
 */
function bindDemoSeederSpies(array &$calls): void
{
    foreach (demoSeederOrder() as $class) {
        app()->instance($class, new class($calls, $class)
        {
            public function __construct(private array &$calls, private string $name) {}

            public function run(): void
            {
                $this->calls[] = $this->name;
            }
        });
    }
}

/**
 * Returns a DemoDataInstaller whose idempotency probe is hard-coded.
 * Avoids touching the real `categories` table from tests since these
 * unit tests don't run migrations.
 */
function demoInstaller(bool $alreadySeeded, bool $familyHasGroups = true): DemoDataInstaller
{
    return new class($alreadySeeded, $familyHasGroups) extends DemoDataInstaller
    {
        public function __construct(
            private bool $alreadySeeded,
            private bool $familyHasGroups,
        ) {}

        public function isAlreadySeeded(): bool
        {
            return $this->alreadySeeded;
        }

        public function defaultFamilyHasGroups(): bool
        {
            return $this->familyHasGroups;
        }
    };
}

describe('DemoDataInstaller::seed (issue #794)', function () {
    it('runs every demo seeder in order, reports each step, and returns success', function () {
        $calls = [];
        bindDemoSeederSpies($calls);

        // Stub elasticsearch off and queue default to sync to avoid touching
        // the real queue/ES while still exercising recalculateCompleteness.
        config(['elasticsearch.enabled' => 'false']);
        Artisan::shouldReceive('registerCommand')->andReturnNull();
        Artisan::shouldReceive('call')
            ->with('unopim:completeness:recalculate', ['--all' => true])
            ->once()
            ->andReturn(0);

        $messages = [];
        $result = demoInstaller(alreadySeeded: false)->seed(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        expect($result)->toBe(['success' => true])
            ->and($calls)->toBe(demoSeederOrder())
            ->and($messages)->toContain('Seeding demo attributes...')
            ->and($messages)->toContain('Seeding demo categories...')
            ->and($messages)->toContain('Seeding demo catalog...')
            ->and($messages)->toContain('Seeding product associations...')
            ->and($messages)->toContain('Recalculating product completeness...');
    });

    it('reports the seeder failure message instead of bubbling the exception', function () {
        app()->instance(DemoCoreSeeder::class, new class
        {
            public function run(): void
            {
                throw new RuntimeException('boom');
            }
        });

        $result = demoInstaller(alreadySeeded: false)->seed();

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toBe('boom');
    });

    it('short-circuits with skipped=true when demo data is already present', function () {
        $calls = [];
        bindDemoSeederSpies($calls);

        $messages = [];
        $result = demoInstaller(alreadySeeded: true)->seed(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        expect($result)->toBe(['success' => true, 'skipped' => true])
            ->and($calls)->toBe([])
            ->and($messages)->toContain('Demo data is already seeded; skipping. Pass --force to re-seed.');
    });

    it('re-seeds even when demo data is already present if force=true', function () {
        $calls = [];
        bindDemoSeederSpies($calls);

        config(['elasticsearch.enabled' => 'false']);
        Artisan::shouldReceive('registerCommand')->andReturnNull();
        Artisan::shouldReceive('call')
            ->with('unopim:completeness:recalculate', ['--all' => true])
            ->once()
            ->andReturn(0);

        $result = demoInstaller(alreadySeeded: true)->seed(null, force: true);

        expect($result)->toBe(['success' => true])
            ->and($calls)->toBe(demoSeederOrder());
    });

    it('returns success=false when the default attribute family has no group mappings after seeding', function () {
        $calls = [];
        bindDemoSeederSpies($calls);

        config(['elasticsearch.enabled' => 'false']);
        Artisan::shouldReceive('registerCommand')->andReturnNull();
        Artisan::shouldReceive('call')
            ->with('unopim:completeness:recalculate', ['--all' => true])
            ->once()
            ->andReturn(0);

        $result = demoInstaller(alreadySeeded: false, familyHasGroups: false)->seed();

        expect($result['success'])->toBeFalse()
            ->and($result['error'])->toContain('default attribute family has no group mappings');
    });

    it('also reindexes elasticsearch when it is enabled', function () {
        $calls = [];
        bindDemoSeederSpies($calls);

        config(['elasticsearch.enabled' => 'true']);

        Artisan::shouldReceive('registerCommand')->andReturnNull();
        Artisan::shouldReceive('call')->with('unopim:category:index')->once()->andReturn(0);
        Artisan::shouldReceive('call')->with('unopim:product:index')->once()->andReturn(0);
        Artisan::shouldReceive('call')
            ->with('unopim:completeness:recalculate', ['--all' => true])
            ->once()
            ->andReturn(0);

        $result = demoInstaller(alreadySeeded: false)->seed();

        expect($result['success'])->toBeTrue();
    });
});
