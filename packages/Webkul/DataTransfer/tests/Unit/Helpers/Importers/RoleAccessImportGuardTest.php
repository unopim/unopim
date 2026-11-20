<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Role\Importer as RoleImporter;
use Webkul\DataTransfer\Helpers\Importers\User\Importer as UserImporter;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->fullRole = Role::factory()->create(['name' => 'Full '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
    $this->limitedRole = Role::factory()->create([
        'name'            => 'Limited '.uniqid(),
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.create', 'settings.users.users.edit'],
    ]);
    $this->subsetRole = Role::factory()->create(['name' => 'Subset '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

    $this->fullAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);
    $this->limitedAdmin = Admin::factory()->create(['role_id' => $this->limitedRole->id, 'status' => 1]);

    $this->loginAsAdmin($this->fullAdmin);
});

function guardedImporter(string $importerClass, string $action, ?int $actorId): RoleImporter|UserImporter
{
    $importer = app($importerClass);
    $importer->setImport(JobTrack::factory()->create(['action' => $action, 'user_id' => $actorId]));
    $importer->setErrorHelper(app(Error::class));

    (function () use ($importer): void {
        $importer instanceof RoleImporter ? $this->roleStorage->init() : $this->userStorage->init();
    })->call($importer);

    return $importer;
}

function writeGuardedBatch(string $importerClass, string $action, ?int $actorId, array $rows): void
{
    $importer = guardedImporter($importerClass, $action, $actorId);

    $importer->importBatch(JobTrackBatch::factory()->create([
        'job_track_id' => JobTrack::factory()->create(['action' => $action, 'user_id' => $actorId]),
        'data'         => $rows,
    ]));
}

function userRow(string $email, string $roleName, array $extra = []): array
{
    return ['name' => 'Imported', 'email' => $email, 'role_name' => $roleName, 'status' => 'active', ...$extra];
}

describe('role importer', function (): void {
    it('refuses rows granting access the importing admin does not hold', function (): void {
        $importer = guardedImporter(RoleImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard,settings.roles'], 2))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 3))->toBeTrue();
    });

    it('refuses changing or deleting a role whose current access exceeds the importing admin', function (): void {
        $append = guardedImporter(RoleImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id);
        $delete = guardedImporter(RoleImporter::class, Import::ACTION_DELETE, $this->limitedAdmin->id);

        expect($append->validateRow(['name' => $this->fullRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeFalse()
            ->and($delete->validateRow(['name' => $this->fullRole->name], 1))->toBeFalse()
            ->and($delete->validateRow(['name' => $this->subsetRole->name], 2))->toBeTrue();
    });

    it('lets a full-access admin grant full access', function (): void {
        $importer = guardedImporter(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin->id);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeTrue();
    });

    it('grants nothing when the job has no known admin', function (): void {
        $importer = guardedImporter(RoleImporter::class, Import::ACTION_APPEND, null);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 2))->toBeFalse();
    });

    it('grants nothing once the admin who started the job is disabled', function (): void {
        $this->fullAdmin->update(['status' => 0]);

        $importer = guardedImporter(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin->id);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeFalse();
    });

    it('skips forbidden rows again when writing the batch', function (): void {
        $name = 'Escalated '.uniqid();

        writeGuardedBatch(RoleImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id, [
            ['name' => $name, 'permission_type' => 'all', 'permissions' => null, 'description' => null],
            ['name' => $this->subsetRole->name, 'permission_type' => 'all', 'permissions' => null, 'description' => null],
        ]);

        writeGuardedBatch(RoleImporter::class, Import::ACTION_DELETE, $this->limitedAdmin->id, [['name' => $this->fullRole->name]]);

        expect(Role::where('name', $name)->exists())->toBeFalse()
            ->and($this->subsetRole->fresh()->permission_type)->toBe('custom')
            ->and(Role::find($this->fullRole->id))->not->toBeNull();
    });
});

describe('user importer', function (): void {
    it('refuses assigning a role the importing admin could not assign', function (): void {
        $importer = guardedImporter(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id);

        expect($importer->validateRow(userRow('a-'.uniqid().'@example.com', $this->fullRole->name), 1))->toBeFalse()
            ->and($importer->validateRow(userRow('b-'.uniqid().'@example.com', $this->subsetRole->name), 2))->toBeTrue();
    });

    it('refuses rewriting the role into access the importing admin does not hold', function (): void {
        $importer = guardedImporter(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id);

        expect($importer->validateRow(userRow('a-'.uniqid().'@example.com', $this->subsetRole->name, ['permission_type' => 'all']), 1))->toBeFalse()
            ->and($importer->validateRow(userRow('b-'.uniqid().'@example.com', $this->subsetRole->name, [
                'permission_type' => 'custom',
                'permissions'     => 'dashboard,settings.roles',
            ]), 2))->toBeFalse();
    });

    it('refuses changing or deleting a user whose current role exceeds the importing admin', function (): void {
        $append = guardedImporter(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id);
        $delete = guardedImporter(UserImporter::class, Import::ACTION_DELETE, $this->limitedAdmin->id);
        $subsetUser = Admin::factory()->create(['role_id' => $this->subsetRole->id]);

        expect($append->validateRow(userRow($this->fullAdmin->email, $this->subsetRole->name), 1))->toBeFalse()
            ->and($delete->validateRow(['email' => $this->fullAdmin->email], 1))->toBeFalse()
            ->and($delete->validateRow(['email' => $subsetUser->email], 2))->toBeTrue();
    });

    it('lets a full-access admin assign full access', function (): void {
        $importer = guardedImporter(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin->id);

        expect($importer->validateRow(userRow('a-'.uniqid().'@example.com', $this->fullRole->name), 1))->toBeTrue();
    });

    it('assigns nothing when the job has no known admin', function (): void {
        $importer = guardedImporter(UserImporter::class, Import::ACTION_APPEND, null);

        expect($importer->validateRow(userRow('a-'.uniqid().'@example.com', $this->subsetRole->name), 1))->toBeFalse();
    });

    it('skips forbidden rows again when writing the batch', function (): void {
        $email = 'escalated-'.uniqid().'@example.com';

        writeGuardedBatch(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin->id, [
            userRow($email, $this->fullRole->name),
            userRow($this->fullAdmin->email, $this->subsetRole->name),
            userRow('other-'.uniqid().'@example.com', $this->subsetRole->name, ['permission_type' => 'all']),
        ]);

        writeGuardedBatch(UserImporter::class, Import::ACTION_DELETE, $this->limitedAdmin->id, [['email' => $this->fullAdmin->email]]);

        expect(Admin::where('email', $email)->exists())->toBeFalse()
            ->and($this->fullAdmin->fresh()->role_id)->toBe($this->fullRole->id)
            ->and($this->subsetRole->fresh()->permission_type)->toBe('custom');
    });
});
