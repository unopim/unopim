<?php

namespace Webkul\DataTransfer\Tests\Unit\Helpers\Exporters\User;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Repositories\LocaleRepository;
use Webkul\DataTransfer\Helpers\Exporters\User\Exporter;
use Webkul\DataTransfer\Helpers\Importers\User\Storage as UserStorage;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;
use Webkul\User\Repositories\AdminRepository;
use Webkul\User\Repositories\RoleRepository;

/**
 * Helper to inject protected properties via reflection
 */
function setProtectedProperty(object $object, string $property, mixed $value): void
{
    $reflection = new \ReflectionClass($object);

    while (! $reflection->hasProperty($property)) {
        $reflection = $reflection->getParentClass();
    }

    $prop = $reflection->getProperty($property);
    $prop->setAccessible(true);
    $prop->setValue($object, $value);
}

describe('User Exporter', function () {
    beforeEach(function () {
        $this->mockRoleRepository = mock(RoleRepository::class);
        $this->mockLocaleRepository = mock(LocaleRepository::class);
        $this->mockUserStorage = mock(UserStorage::class);
        $this->mockAdminRepository = mock(AdminRepository::class);

        $this->exporter = new Exporter(
            app(JobTrackBatchRepository::class),
            app(FlatItemBuffer::class),
            $this->mockRoleRepository,
            $this->mockLocaleRepository,
            $this->mockUserStorage
        );
    });

    it('calls userStorage->init() during initialization', function () {
        $this->mockUserStorage->shouldReceive('init')->once();

        // Partially mock the exporter to bypass initializeFileBuffer (which needs a real export model/file system)
        $exporter = \Mockery::mock(Exporter::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $exporter->shouldReceive('initializeFileBuffer')->once()->andReturnNull();

        // Inject mocked userStorage via reflection
        setProtectedProperty($exporter, 'userStorage', $this->mockUserStorage);

        $exporter->initilize();
    });

    it('can prepare users data for export', function () {
        $usersData = [
            [
                'id'           => 1,
                'name'         => 'Admin User',
                'email'        => 'admin@example.com',
                'status'       => 1,
                'role_id'      => 1,
                'timezone'     => 'UTC',
                'ui_locale_id' => 1,
            ],
            [
                'id'           => 2,
                'name'         => 'Manager User',
                'email'        => 'manager@example.com',
                'status'       => 0,
                'role_id'      => 2,
                'timezone'     => 'America/New_York',
                'ui_locale_id' => 2,
            ],
        ];

        $batch = new JobTrackBatch(['data' => $usersData]);

        $roles = collect([
            (object) ['id' => 1, 'name' => 'Administrator', 'permission_type' => 'all', 'permissions' => null],
            (object) ['id' => 2, 'name' => 'Manager', 'permission_type' => 'custom', 'permissions' => ['dashboard']],
        ]);

        $locales = collect([
            (object) ['id' => 1, 'code' => 'en_US'],
            (object) ['id' => 2, 'code' => 'fr_FR'],
        ]);

        $this->mockRoleRepository->shouldReceive('all')->andReturn($roles);
        $this->mockLocaleRepository->shouldReceive('all')->andReturn($locales);

        $preparedData = $this->exporter->prepareUsers($batch);

        expect($preparedData)->toBeArray();
        expect($preparedData)->toHaveCount(2);

        expect($preparedData[0]['name'])->toBe('Admin User');
        expect($preparedData[0]['role_name'])->toBe('Administrator');
        expect($preparedData[0]['permission_type'])->toBe('all');
        expect($preparedData[0]['permissions'])->toBe('');
        expect($preparedData[0]['image'])->toBe('');
        expect($preparedData[0]['status'])->toBe('active');
        expect($preparedData[0]['timezone'])->toBe('UTC');
        expect($preparedData[0]['ui_locale_code'])->toBe('en_US');

        expect($preparedData[1]['name'])->toBe('Manager User');
        expect($preparedData[1]['role_name'])->toBe('Manager');
        expect($preparedData[1]['permission_type'])->toBe('custom');
        expect($preparedData[1]['permissions'])->toBe('dashboard');
        expect($preparedData[1]['image'])->toBe('');
        expect($preparedData[1]['status'])->toBe('inactive');
        expect($preparedData[1]['timezone'])->toBe('America/New_York');
        expect($preparedData[1]['ui_locale_code'])->toBe('fr_FR');
    });

    it('increments createdItemsCount after preparing users', function () {
        $usersData = [
            ['id' => 1, 'name' => 'User A', 'email' => 'a@example.com', 'status' => 1, 'role_id' => 1, 'timezone' => 'UTC', 'ui_locale_id' => 1],
            ['id' => 2, 'name' => 'User B', 'email' => 'b@example.com', 'status' => 0, 'role_id' => 1, 'timezone' => 'UTC', 'ui_locale_id' => 1],
        ];

        $batch = new JobTrackBatch(['data' => $usersData]);

        $this->mockRoleRepository->shouldReceive('all')->andReturn(collect([]));
        $this->mockLocaleRepository->shouldReceive('all')->andReturn(collect([]));

        $this->exporter->prepareUsers($batch);

        expect($this->exporter->getCreatedItemsCount())->toBe(2);
    });

    it('filters results by active status via getResults', function () {
        // Inject filters directly using reflection, bypassing $export dependency
        setProtectedProperty($this->exporter, 'filters', ['status' => 'active']);
        setProtectedProperty($this->exporter, 'source', $this->mockAdminRepository);

        $mockQuery = mock(Builder::class);
        $this->mockAdminRepository->shouldReceive('query')->andReturn($mockQuery);
        $mockQuery->shouldReceive('where')->with('status', 1)->once()->andReturnSelf();
        $mockQuery->shouldReceive('get')->andReturn(collect([]));

        $reflection = new \ReflectionClass(Exporter::class);
        $method = $reflection->getMethod('getResults');
        $method->setAccessible(true);
        $result = $method->invoke($this->exporter);

        expect($result)->toBeInstanceOf(\ArrayIterator::class);
    });

    it('does not filter results when status filter is all', function () {
        // Inject filters directly — no WHERE clause should be applied
        setProtectedProperty($this->exporter, 'filters', ['status' => 'all']);
        setProtectedProperty($this->exporter, 'source', $this->mockAdminRepository);

        $mockQuery = mock(Builder::class);
        $this->mockAdminRepository->shouldReceive('query')->andReturn($mockQuery);
        // 'where' should NOT be called when status is 'all'
        $mockQuery->shouldNotReceive('where');
        $mockQuery->shouldReceive('get')->andReturn(collect([]));

        $reflection = new \ReflectionClass(Exporter::class);
        $method = $reflection->getMethod('getResults');
        $method->setAccessible(true);
        $method->invoke($this->exporter);
    });

    it('gracefully handles missing roles and locales during preparation', function () {
        $usersData = [
            [
                'id'           => 1,
                'name'         => 'Ghost User',
                'email'        => 'ghost@example.com',
                'status'       => 1,
                'role_id'      => 999, // Non-existent
                'timezone'     => 'UTC',
                'ui_locale_id' => 999, // Non-existent
            ],
        ];

        $batch = new JobTrackBatch(['data' => $usersData]);

        $this->mockRoleRepository->shouldReceive('all')->andReturn(collect([]));
        $this->mockLocaleRepository->shouldReceive('all')->andReturn(collect([]));

        $preparedData = $this->exporter->prepareUsers($batch);

        expect($preparedData[0]['role_name'])->toBe('');
        expect($preparedData[0]['permission_type'])->toBe('');
        expect($preparedData[0]['permissions'])->toBe('');
        expect($preparedData[0]['ui_locale_code'])->toBe('');
    });

    it('exports permissions as a comma-separated string', function () {
        $usersData = [
            ['id' => 1, 'name' => 'Editor', 'email' => 'editor@example.com', 'status' => 1, 'role_id' => 1, 'timezone' => 'UTC', 'ui_locale_id' => 1],
        ];

        $batch = new JobTrackBatch(['data' => $usersData]);

        $roles = collect([
            (object) ['id' => 1, 'name' => 'Editor Role', 'permission_type' => 'custom', 'permissions' => ['dashboard', 'catalog.products', 'catalog.categories']],
        ]);

        $this->mockRoleRepository->shouldReceive('all')->andReturn($roles);
        $this->mockLocaleRepository->shouldReceive('all')->andReturn(collect([]));

        $preparedData = $this->exporter->prepareUsers($batch);

        expect($preparedData[0]['permissions'])->toBe('dashboard,catalog.products,catalog.categories');
    });

    it('copies exported user images using their stored relative path when with_media is enabled', function () {
        Storage::fake('public');

        Storage::disk('public')->put('admins/1/avatar.jpg', 'fake-image');

        setProtectedProperty($this->exporter, 'filters', ['with_media' => 1]);

        $usersData = [
            ['id' => 1, 'name' => 'Editor', 'email' => 'editor@example.com', 'image' => 'admins/1/avatar.jpg', 'status' => 1, 'role_id' => 1, 'timezone' => 'UTC', 'ui_locale_id' => 1],
        ];

        $batch = new JobTrackBatch(['data' => $usersData]);

        $this->mockRoleRepository->shouldReceive('all')->andReturn(collect([]));
        $this->mockLocaleRepository->shouldReceive('all')->andReturn(collect([]));

        $filePath = new class
        {
            public function getTemporaryPath(): string
            {
                return 'exports/tmp';
            }
        };

        $preparedData = $this->exporter->prepareUsers($batch, $filePath);

        expect($preparedData[0]['image'])->toBe('admins/1/avatar.jpg');
        expect(Storage::disk('public')->exists('exports/tmp/admins/1/avatar.jpg'))->toBeTrue();
    });
});
