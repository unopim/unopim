<?php

use Illuminate\Support\Facades\Artisan;
use Webkul\Installer\Database\Seeders\CategoryDemoTableSeeder;
use Webkul\Installer\Database\Seeders\DemoExtrasTableSeeder;
use Webkul\Installer\Database\Seeders\ProductTableSeeder;
use Webkul\Installer\Helpers\DemoDataInstaller;

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
        // Replace the three demo seeders with no-op spies so the test does not
        // touch the JSON fixtures (slow) and we can verify call order.
        $calls = [];
        foreach ([
            DemoExtrasTableSeeder::class,
            CategoryDemoTableSeeder::class,
            ProductTableSeeder::class,
        ] as $class) {
            app()->instance($class, new class($calls, $class)
            {
                public function __construct(private array &$calls, private string $name) {}

                public function run(): void
                {
                    $this->calls[] = $this->name;
                }
            });
        }

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
            ->and($calls)->toBe([
                DemoExtrasTableSeeder::class,
                CategoryDemoTableSeeder::class,
                ProductTableSeeder::class,
            ])
            ->and($messages)->toContain('Seeding demo extras (channels, attributes, families, core config, ...)...')
            ->and($messages)->toContain('Seeding demo categories...')
            ->and($messages)->toContain('Seeding sample products...')
            ->and($messages)->toContain('Recalculating product completeness...');
    });

    it('reports the seeder failure message instead of bubbling the exception', function () {
        app()->instance(DemoExtrasTableSeeder::class, new class
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
        $invoked = false;
        foreach ([
            DemoExtrasTableSeeder::class,
            CategoryDemoTableSeeder::class,
            ProductTableSeeder::class,
        ] as $class) {
            app()->instance($class, new class($invoked)
            {
                public function __construct(private bool &$invoked) {}

                public function run(): void
                {
                    $this->invoked = true;
                }
            });
        }

        $messages = [];
        $result = demoInstaller(alreadySeeded: true)->seed(function (string $message) use (&$messages): void {
            $messages[] = $message;
        });

        expect($result)->toBe(['success' => true, 'skipped' => true])
            ->and($invoked)->toBeFalse()
            ->and($messages)->toContain('Demo data is already seeded; skipping. Pass --force to re-seed.');
    });

    it('re-seeds even when demo data is already present if force=true', function () {
        $calls = [];
        foreach ([
            DemoExtrasTableSeeder::class,
            CategoryDemoTableSeeder::class,
            ProductTableSeeder::class,
        ] as $class) {
            app()->instance($class, new class($calls, $class)
            {
                public function __construct(private array &$calls, private string $name) {}

                public function run(): void
                {
                    $this->calls[] = $this->name;
                }
            });
        }

        config(['elasticsearch.enabled' => 'false']);
        Artisan::shouldReceive('registerCommand')->andReturnNull();
        Artisan::shouldReceive('call')
            ->with('unopim:completeness:recalculate', ['--all' => true])
            ->once()
            ->andReturn(0);

        $result = demoInstaller(alreadySeeded: true)->seed(null, force: true);

        expect($result)->toBe(['success' => true])
            ->and($calls)->toBe([
                DemoExtrasTableSeeder::class,
                CategoryDemoTableSeeder::class,
                ProductTableSeeder::class,
            ]);
    });

    it('returns success=false when the default attribute family has no group mappings after seeding', function () {
        foreach ([
            DemoExtrasTableSeeder::class,
            CategoryDemoTableSeeder::class,
            ProductTableSeeder::class,
        ] as $class) {
            app()->instance($class, new class
            {
                public function run(): void {}
            });
        }

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
        foreach ([
            DemoExtrasTableSeeder::class,
            CategoryDemoTableSeeder::class,
            ProductTableSeeder::class,
        ] as $class) {
            app()->instance($class, new class
            {
                public function run(): void {}
            });
        }

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
