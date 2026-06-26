<?php

namespace Webkul\DataTransfer\Tests\Unit\Helpers\Importers\Role;

use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Role\Importer;
use Webkul\DataTransfer\Helpers\Source;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Role;

describe('Role Importer', function () {
    beforeEach(function () {
        $this->loginAsAdmin();
    });

    it('can validate a row for role import', function () {
        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $validRow = [
            'name'            => 'Test Role',
            'permission_type' => 'all',
            'description'     => 'Test Description',
        ];

        expect($importer->validateRow($validRow, 1))->toBeTrue();

        $invalidRow = [
            'name'            => '', // name required
            'permission_type' => 'invalid', // invalid permission type
        ];

        expect($importer->validateRow($invalidRow, 2))->toBeFalse();
    });

    it('can save role data during importBatch', function () {
        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_APPEND,
        ]);

        $rolesData = [
            [
                'name'            => 'New Role '.uniqid(),
                'description'     => 'New Description',
                'permission_type' => 'all',
                'permissions'     => '',
            ],
            [
                'name'            => 'Custom Role '.uniqid(),
                'description'     => 'Custom Description',
                'permission_type' => 'custom',
                'permissions'     => 'dashboard,settings',
            ],
        ];

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => $rolesData,
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $importer->importBatch($batch);

        foreach ($rolesData as $roleData) {
            $this->assertDatabaseHas('roles', [
                'name'            => $roleData['name'],
                'permission_type' => $roleData['permission_type'],
            ]);

            if ($roleData['permission_type'] === 'custom') {
                $role = Role::where('name', $roleData['name'])->first();
                expect($role->permissions)->toBe(['dashboard', 'settings']);
            }
        }
    });

    it('can delete role data during importBatch', function () {
        $roleName = 'Delete Me '.uniqid();
        $role = Role::create([
            'name'            => $roleName,
            'permission_type' => 'all',
        ]);

        $jobTrack = JobTrack::factory()->create([
            'action' => Import::ACTION_DELETE,
        ]);

        $batch = JobTrackBatch::factory()->create([
            'job_track_id' => $jobTrack->id,
            'data'         => [['name' => $roleName]],
        ]);

        $importer = app(Importer::class);
        $importer->setImport($jobTrack);
        $importer->setErrorHelper(app(Error::class));

        $source = mock(Source::class);
        $source->shouldReceive('getColumnNames')->andReturn(['name']);
        $importer->setSource($source);

        $importer->validateData(); // Initializes storage

        $importer->importBatch($batch);

        $this->assertDatabaseMissing('roles', ['name' => $roleName]);
    });
});
