<?php

namespace Webkul\DataTransfer\Tests\Unit\Helpers\Importers\User;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\User\Importer;
use Webkul\DataTransfer\Helpers\Source;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

describe('User Importer', function () {
    beforeEach(function () {
        $this->loginAsAdmin();

        // Ensure we have a role and locale
        $this->role = Role::first() ?: Role::create(['name' => 'Administrator', 'permission_type' => 'all']);
        $this->locale = Locale::first() ?: Locale::create(['code' => 'en_US', 'name' => 'English']);
    });

    it('can validate a row for user import (including role permissions)', function () {
        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn($importer->getValidColumnNames());
        $source->shouldReceive('rewind');
        $source->shouldReceive('valid')->andReturn(false);
        $importer->setSource($source);

        $importer->validateData(); // Initialize storage

        $validRow = [
            'name'            => 'Test User',
            'email'           => 'test@example.com',
            'role_name'       => $this->role->name,
            'permission_type' => 'all',
            'status'          => 'active',
            'ui_locale_code'  => $this->locale->code,
        ];

        expect($importer->validateRow($validRow, 1))->toBeTrue();

        $invalidRow = [
            'name'            => '', // name required
            'email'           => 'invalid-email', // invalid email
            'role_name'       => 'Some Role',
            'permission_type' => 'invalid', // invalid permission type
        ];

        expect($importer->validateRow($invalidRow, 2))->toBeFalse();
    });

    it('can create a role and user during importBatch', function () {
        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $roleName = 'New Manager '.uniqid();
        $usersData = [
            [
                'name'            => 'New User '.uniqid(),
                'email'           => 'new'.uniqid().'@example.com',
                'status'          => 'active',
                'role_name'       => $roleName,
                'permission_type' => 'custom',
                'permissions'     => 'dashboard,catalog.products',
                'ui_locale_code'  => $this->locale->code,
            ],
        ];

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn($importer->getValidColumnNames());
        $source->shouldReceive('rewind');
        $source->shouldReceive('valid')->andReturn(false);
        $importer->setSource($source);

        $importer->validateData(); // Initialize storage

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => $usersData,
        ]);

        $importer->importBatch($batch);

        // Assert role created
        $this->assertDatabaseHas('roles', [
            'name'            => $roleName,
            'permission_type' => 'custom',
        ]);

        $role = Role::where('name', $roleName)->first();
        expect($role->permissions)->toEqual(['dashboard', 'catalog.products']);

        // Assert user created with new role
        foreach ($usersData as $userData) {
            $this->assertDatabaseHas('admins', [
                'email'   => $userData['email'],
                'role_id' => $role->id,
            ]);

            $admin = Admin::where('email', $userData['email'])->first();
            expect($admin->password)->toBeNull();
        }
    });

    it('can update role permissions during user import', function () {
        $roleName = 'Existing Role '.uniqid();
        $role = Role::create([
            'name'            => $roleName,
            'permission_type' => 'all',
        ]);

        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $usersData = [
            [
                'name'            => 'Update Role User',
                'email'           => 'updater'.uniqid().'@example.com',
                'role_name'       => $roleName,
                'permission_type' => 'custom',
                'permissions'     => 'catalog.categories',
            ],
        ];

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn($importer->getValidColumnNames());
        $source->shouldReceive('rewind');
        $source->shouldReceive('valid')->andReturn(false);
        $importer->setSource($source);

        $importer->validateData();

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => $usersData,
        ]);

        $importer->importBatch($batch);

        // Assert role updated
        $role->refresh();
        expect($role->permission_type)->toBe('custom');
        expect($role->permissions)->toEqual(['catalog.categories']);
    });

    it('imports only active users when the import profile filter is active', function () {
        $jobInstance = JobInstances::factory()->create([
            'entity_type' => 'users',
            'type'        => 'import',
            'action'      => Import::ACTION_APPEND,
            'filters'     => ['status' => 'active'],
        ]);

        $jobTrack = JobTrack::factory()->create([
            'job_instances_id' => $jobInstance->id,
            'action'           => Import::ACTION_APPEND,
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack->fresh('jobInstance'));
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn($importer->getValidColumnNames());
        $source->shouldReceive('rewind');
        $source->shouldReceive('valid')->andReturn(false);
        $importer->setSource($source);

        $importer->validateData();

        $activeUser = [
            'name'           => 'Active Import User',
            'email'          => 'active-import-'.uniqid().'@example.com',
            'status'         => 'active',
            'role_name'      => $this->role->name,
            'ui_locale_code' => $this->locale->code,
        ];

        $inactiveUser = [
            'name'           => 'Inactive Import User',
            'email'          => 'inactive-import-'.uniqid().'@example.com',
            'status'         => 'inactive',
            'role_name'      => $this->role->name,
            'ui_locale_code' => $this->locale->code,
        ];

        expect($importer->validateRow($activeUser, 1))->toBeTrue();
        expect($importer->validateRow($inactiveUser, 2))->toBeFalse();

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => [$activeUser],
        ]);

        $importer->importBatch($batch);

        $this->assertDatabaseHas('admins', [
            'email' => $activeUser['email'],
            'name'  => $activeUser['name'],
        ]);

        $this->assertDatabaseMissing('admins', [
            'email' => $inactiveUser['email'],
        ]);
    });

    it('can delete user data during importBatch', function () {
        $email = 'delete'.uniqid().'@example.com';
        Admin::create([
            'name'     => 'Delete Me',
            'email'    => $email,
            'password' => Hash::make('password'),
            'role_id'  => $this->role->id,
        ]);

        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_DELETE,
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn(['email']);
        $source->shouldReceive('rewind');
        $source->shouldReceive('valid')->andReturn(false);
        $importer->setSource($source);

        $importer->validateData();

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => [['email' => $email]],
        ]);

        $importer->importBatch($batch);

        $this->assertDatabaseMissing('admins', ['email' => $email]);
    });
});
