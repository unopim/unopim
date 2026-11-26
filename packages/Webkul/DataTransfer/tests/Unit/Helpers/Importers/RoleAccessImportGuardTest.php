<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Psr\Log\LoggerInterface;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Role\Importer as RoleImporter;
use Webkul\DataTransfer\Helpers\Importers\User\Importer as UserImporter;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->fullRole = Role::factory()->create(['name' => 'Full '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
    $this->limitedRole = Role::factory()->create([
        'name'            => 'Limited '.uniqid(),
        'permission_type' => 'custom',
        'permissions'     => [
            'dashboard',
            'data_transfer.imports',
            'settings.roles.create',
            'settings.roles.edit',
            'settings.users.users.create',
            'settings.users.users.edit',
        ],
    ]);

    Admin::query()->update(['role_id' => $this->limitedRole->id]);
    Role::query()->where('permission_type', 'all')->whereKeyNot($this->fullRole->id)->update(['permission_type' => 'custom']);

    $this->fullAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);
    $this->limitedAdmin = Admin::factory()->create(['role_id' => $this->limitedRole->id, 'status' => 1]);

    $this->loginAsAdmin($this->fullAdmin);
});

function importerFor(string $importerClass, string $action, ?Admin $initiator, ?Admin $uploader = null, ?Admin $editor = null): RoleImporter|UserImporter
{
    return importerWithMeta($importerClass, $action, $initiator, [
        'updated_by'       => ($editor ?? $uploader ?? $initiator)?->id,
        'file_uploaded_by' => ($uploader ?? $initiator)?->id,
    ]);
}

function importerWithMeta(string $importerClass, string $action, ?Admin $initiator, array $meta): RoleImporter|UserImporter
{
    $importer = app($importerClass);
    $importer->setImport(JobTrack::factory()->create([
        'action'  => $action,
        'user_id' => $initiator?->id,
        'meta'    => ['batch_count' => 0, ...$meta],
    ]));
    $importer->setErrorHelper(app(Error::class));

    (fn () => $importer instanceof RoleImporter ? $this->roleStorage->init() : $this->userStorage->init())->call($importer);

    return $importer;
}

function writeImportBatch(string $importerClass, string $action, array $rows, ?Admin $actor = null, ?LoggerInterface $logger = null): void
{
    $importer = importerFor($importerClass, $action, $actor);

    if ($logger) {
        $importer->setLogger($logger);
    }

    $importer->importBatch(JobTrackBatch::factory()->create([
        'job_track_id' => JobTrack::factory()->create(['action' => $action]),
        'data'         => $rows,
    ]));
}

function hasActiveFullAccessAdmin(): bool
{
    return Admin::query()
        ->where('status', 1)
        ->whereIn('role_id', Role::query()->where('permission_type', 'all')->select('id'))
        ->exists();
}

describe('role importer', function () {
    it('rejects a role granting full access when the importing admin lacks it', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->limitedAdmin);

        expect($importer->validateRow(['name' => $this->limitedRole->name, 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'settings.users'], 2))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 3))->toBeTrue();
    });

    it('lets a full-access admin grant full access', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeTrue();
    });

    it('grants nothing for a job whose admin is unknown or deleted', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, null);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 2))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom'], 3))->toBeFalse();
    });

    it('rejects demoting the last active full-access role', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow(['name' => $this->fullRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeFalse();
    });

    it('rejects the demotion that would remove the last full-access role across rows of one file', function () {
        $secondFullRole = Role::factory()->create(['name' => 'Full B '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
        Admin::factory()->create(['role_id' => $secondFullRole->id, 'status' => 1]);

        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow(['name' => $this->fullRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeTrue()
            ->and($importer->validateRow(['name' => $secondFullRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 2))->toBeFalse();
    });

    it('rejects a full-access grant from a limited admin who uploaded the file even when a full-access admin runs it', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin, $this->limitedAdmin);

        expect($importer->validateRow(['name' => $this->limitedRole->name, 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 2))->toBeTrue();
    });

    it('re-checks the last full-access role when writing two files that were validated one after another', function () {
        $secondFullRole = Role::factory()->create(['name' => 'Full B '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
        Admin::factory()->create(['role_id' => $secondFullRole->id, 'status' => 1]);

        $demote = fn (Role $role): array => ['name' => $role->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'];

        expect(importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin)->validateRow($demote($this->fullRole), 1))->toBeTrue()
            ->and(importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin)->validateRow($demote($secondFullRole), 1))->toBeTrue();

        writeImportBatch(RoleImporter::class, Import::ACTION_APPEND, [$demote($this->fullRole)]);
        writeImportBatch(RoleImporter::class, Import::ACTION_APPEND, [$demote($secondFullRole)]);

        expect(hasActiveFullAccessAdmin())->toBeTrue();
    });

    it('accepts changing a custom role while no active admin holds full access', function () {
        Admin::query()->update(['status' => 0]);

        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow(['name' => $this->limitedRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeTrue();
    });

    it('rejects role rows from a runner or uploader without the matching role permission', function (string $action) {
        $unusedRole = Role::factory()->create(['name' => 'Unused '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
        $importOnlyAdmin = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports'],
        ])->id]);

        $row = ['name' => $unusedRole->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'];

        expect(importerFor(RoleImporter::class, $action, $importOnlyAdmin)->validateRow($row, 1))->toBeFalse()
            ->and(importerFor(RoleImporter::class, $action, $this->fullAdmin, $importOnlyAdmin)->validateRow($row, 1))->toBeFalse()
            ->and(importerFor(RoleImporter::class, $action, $this->fullAdmin)->validateRow($row, 1))->toBeTrue();
    })->with(['edit' => Import::ACTION_APPEND, 'delete' => Import::ACTION_DELETE]);

    it('skips deleting a role that gained admins after its file was validated', function () {
        $role = Role::factory()->create(['name' => 'Soon Used '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        expect(importerFor(RoleImporter::class, Import::ACTION_DELETE, $this->fullAdmin)->validateRow(['name' => $role->name], 1))->toBeTrue();

        Admin::factory()->create(['role_id' => $role->id, 'status' => 1]);

        writeImportBatch(RoleImporter::class, Import::ACTION_DELETE, [['name' => $role->name]]);

        expect(Role::find($role->id))->not->toBeNull();
    });

    it('skips a row validated as a new role when a role with that name appears before the write and the importing admin cannot edit roles', function () {
        $createOnlyAdmin = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.roles.create'],
        ])->id, 'status' => 1]);

        $name = 'Appears Later '.uniqid();
        $row = ['name' => $name, 'permission_type' => 'custom', 'permissions' => 'dashboard', 'description' => 'Overwritten'];

        expect(importerFor(RoleImporter::class, Import::ACTION_APPEND, $createOnlyAdmin)->validateRow($row, 1))->toBeTrue();

        $role = Role::factory()->create(['name' => $name, 'description' => 'Original', 'permission_type' => 'all', 'permissions' => null]);

        writeImportBatch(RoleImporter::class, Import::ACTION_APPEND, [$row], $createOnlyAdmin);

        $role->refresh();

        expect($role->permission_type)->toBe('all')
            ->and($role->description)->toBe('Original');
    });

    it('records in the job log why a validated role row was not written', function () {
        $createOnlyAdmin = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.roles.create'],
        ])->id, 'status' => 1]);

        $name = 'Appears Later '.uniqid();
        Role::factory()->create(['name' => $name, 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        $logger = Mockery::spy(LoggerInterface::class);

        writeImportBatch(RoleImporter::class, Import::ACTION_APPEND, [['name' => $name, 'permission_type' => 'custom', 'permissions' => 'dashboard']], $createOnlyAdmin, $logger);

        $logger->shouldHaveReceived('warning')->once()->withArgs(fn (string $message): bool => str_contains($message, $name)
            && str_contains($message, trans('admin::app.common.unauthorized')));
    });

    it('rejects a role row when the admin who last edited the profile lacks the access, even if full-access admins uploaded and run it', function () {
        $importer = importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin, $this->fullAdmin, $this->limitedAdmin);

        expect($importer->validateRow(['name' => $this->limitedRole->name, 'permission_type' => 'all'], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 2))->toBeTrue();
    });

    it('checks a profile saved before its editor and uploader were recorded against the admin running it', function () {
        expect(importerWithMeta(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin, [])->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeTrue()
            ->and(importerWithMeta(RoleImporter::class, Import::ACTION_APPEND, $this->limitedAdmin, [])->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'all'], 1))->toBeFalse();
    });

    it('grants nothing when the recorded editor or uploader of the profile no longer exists', function (string $key) {
        $goneAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);
        $goneAdminId = $goneAdmin->id;
        $goneAdmin->delete();

        $importer = importerWithMeta(RoleImporter::class, Import::ACTION_APPEND, $this->fullAdmin, [$key => $goneAdminId]);

        expect($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeFalse();
    })->with(['editor' => 'updated_by', 'uploader' => 'file_uploaded_by']);

    it('skips deleting a role when the importing admin lost the delete permission after validation', function () {
        $deleterRole = Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.roles.delete'],
        ]);
        $deleter = Admin::factory()->create(['role_id' => $deleterRole->id, 'status' => 1]);
        $unusedRole = Role::factory()->create(['name' => 'Unused '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        expect(importerFor(RoleImporter::class, Import::ACTION_DELETE, $deleter)->validateRow(['name' => $unusedRole->name], 1))->toBeTrue();

        $deleterRole->update(['permissions' => ['dashboard', 'data_transfer.imports']]);

        writeImportBatch(RoleImporter::class, Import::ACTION_DELETE, [['name' => $unusedRole->name]], $deleter);

        expect(Role::find($unusedRole->id))->not->toBeNull();
    });

    it('rejects deleting a role that admins still use', function () {
        $unusedRole = Role::factory()->create(['name' => 'Unused '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        $importer = importerFor(RoleImporter::class, Import::ACTION_DELETE, $this->fullAdmin);

        expect($importer->validateRow(['name' => $this->limitedRole->name], 1))->toBeFalse()
            ->and($importer->validateRow(['name' => $unusedRole->name], 2))->toBeTrue();
    });
});

describe('user importer', function () {
    it('rejects an admin assigning itself the full-access role', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin);

        expect($importer->validateRow([
            'name'      => $this->limitedAdmin->name,
            'email'     => $this->limitedAdmin->email,
            'role_name' => $this->fullRole->name,
        ], 1))->toBeFalse();
    });

    it('rejects assigning the full-access role from a file a limited admin uploaded even when a full-access admin runs it', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin, $this->limitedAdmin);

        expect($importer->validateRow([
            'name'      => $this->limitedAdmin->name,
            'email'     => $this->limitedAdmin->email,
            'role_name' => $this->fullRole->name,
        ], 1))->toBeFalse();
    });

    it('lets a full-access admin assign the full-access role from a file a full-access admin uploaded', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin, Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]));

        expect($importer->validateRow([
            'name'      => $this->limitedAdmin->name,
            'email'     => $this->limitedAdmin->email,
            'role_name' => $this->fullRole->name,
        ], 1))->toBeTrue();
    });

    it('re-checks the last full-access admin when writing two files that were validated one after another', function (string $action) {
        $secondFullAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);

        $rowFor = fn (Admin $admin): array => [
            'name'      => 'Demoted User',
            'email'     => $admin->email,
            'role_name' => $this->limitedRole->name,
        ];

        expect(importerFor(UserImporter::class, $action, $this->fullAdmin)->validateRow($rowFor($this->fullAdmin), 1))->toBeTrue()
            ->and(importerFor(UserImporter::class, $action, $this->fullAdmin)->validateRow($rowFor($secondFullAdmin), 1))->toBeTrue();

        $writer = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 0]);

        writeImportBatch(UserImporter::class, $action, [$rowFor($this->fullAdmin)], $writer);
        writeImportBatch(UserImporter::class, $action, [$rowFor($secondFullAdmin)], $writer);

        expect(hasActiveFullAccessAdmin())->toBeTrue();
    })->with([
        'moved to a custom role' => Import::ACTION_APPEND,
        'deleted'                => Import::ACTION_DELETE,
    ]);

    it('rejects user rows from a runner or uploader without the matching user permission', function (string $action) {
        $importOnlyAdmin = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports'],
        ])->id]);

        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        $row = ['name' => 'Other User', 'email' => $this->limitedAdmin->email, 'role_name' => $heldRole->name];

        expect(importerFor(UserImporter::class, $action, $importOnlyAdmin)->validateRow($row, 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, $action, $this->fullAdmin, $importOnlyAdmin)->validateRow($row, 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, $action, $this->fullAdmin)->validateRow($row, 1))->toBeTrue();
    })->with(['edit' => Import::ACTION_APPEND, 'delete' => Import::ACTION_DELETE]);

    it('rejects a user row that rewrites a role definition unless the runner and uploader hold role rights', function () {
        $userManagerRole = Role::factory()->create([
            'name'            => 'User Manager '.uniqid(),
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.create', 'settings.users.users.edit'],
        ]);
        $userManager = Admin::factory()->create(['role_id' => $userManagerRole->id, 'status' => 1]);
        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
        $managedUser = Admin::factory()->create(['role_id' => $heldRole->id, 'status' => 1]);

        $rewriteRow = [
            'name'            => 'Other User',
            'email'           => $managedUser->email,
            'role_name'       => $heldRole->name,
            'permission_type' => 'custom',
            'permissions'     => 'dashboard',
        ];
        $assignRow = array_diff_key($rewriteRow, array_flip(['permission_type', 'permissions']));

        expect(importerFor(UserImporter::class, Import::ACTION_APPEND, $userManager)->validateRow($rewriteRow, 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin, $userManager)->validateRow($rewriteRow, 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, Import::ACTION_APPEND, $userManager)->validateRow($assignRow, 1))->toBeTrue()
            ->and(importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin)->validateRow($rewriteRow, 1))->toBeTrue();
    });

    it('rejects changing or deleting an existing admin whose current role the runner or uploader could not assign', function (string $action, string $targetRole) {
        $userManager = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.create', 'settings.users.users.edit', 'settings.users.users.delete'],
        ])->id, 'status' => 1]);
        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);

        Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);

        $privileged = Admin::factory()->create(['role_id' => $this->{$targetRole}->id, 'status' => 1]);
        $managed = Admin::factory()->create(['role_id' => $heldRole->id, 'status' => 1]);

        $rowFor = fn (Admin $admin): array => ['name' => 'Taken Over', 'email' => $admin->email, 'role_name' => $heldRole->name];

        expect(importerFor(UserImporter::class, $action, $userManager)->validateRow($rowFor($privileged), 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, $action, $this->fullAdmin, $userManager)->validateRow($rowFor($privileged), 1))->toBeFalse()
            ->and(importerFor(UserImporter::class, $action, $userManager)->validateRow($rowFor($managed), 1))->toBeTrue()
            ->and(importerFor(UserImporter::class, $action, $this->fullAdmin)->validateRow($rowFor($privileged), 1))->toBeTrue();
    })->with([
        'edit full access'           => [Import::ACTION_APPEND, 'fullRole'],
        'edit wider custom access'   => [Import::ACTION_APPEND, 'limitedRole'],
        'delete full access'         => [Import::ACTION_DELETE, 'fullRole'],
        'delete wider custom access' => [Import::ACTION_DELETE, 'limitedRole'],
    ]);

    it('skips writing a row for an admin whose role was raised beyond the importing admin after validation', function (string $action) {
        $userManager = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.edit', 'settings.users.users.delete'],
        ])->id, 'status' => 1]);
        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
        $target = Admin::factory()->create(['name' => 'Original Name', 'role_id' => $heldRole->id, 'status' => 1]);

        $row = ['name' => 'Taken Over', 'email' => $target->email, 'role_name' => $heldRole->name];

        expect(importerFor(UserImporter::class, $action, $userManager)->validateRow($row, 1))->toBeTrue();

        $target->update(['role_id' => $this->fullRole->id]);

        writeImportBatch(UserImporter::class, $action, [$row], $userManager);

        $target = Admin::find($target->id);

        expect($target)->not->toBeNull()
            ->and($target->name)->toBe('Original Name')
            ->and($target->role_id)->toBe($this->fullRole->id);
    })->with(['edit' => Import::ACTION_APPEND, 'delete' => Import::ACTION_DELETE]);

    it('skips a row validated as a new user when that user appears before the write and the importing admin cannot edit users', function () {
        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
        $createOnlyAdmin = Admin::factory()->create(['role_id' => Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.create'],
        ])->id, 'status' => 1]);

        $email = 'appears-later-'.uniqid().'@example.com';
        $row = ['name' => 'Taken Over', 'email' => $email, 'role_name' => $heldRole->name];

        expect(importerFor(UserImporter::class, Import::ACTION_APPEND, $createOnlyAdmin)->validateRow($row, 1))->toBeTrue();

        $user = Admin::factory()->create(['name' => 'Original Name', 'email' => $email, 'role_id' => $heldRole->id, 'status' => 1]);

        writeImportBatch(UserImporter::class, Import::ACTION_APPEND, [$row], $createOnlyAdmin);

        expect($user->fresh()->name)->toBe('Original Name');
    });

    it('re-reads the importing admin when the same importer validates and then writes the batch', function () {
        $managerRole = Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.users.users.edit'],
        ]);
        $manager = Admin::factory()->create(['role_id' => $managerRole->id, 'status' => 1]);
        $heldRole = Role::factory()->create(['name' => 'Held '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard']]);
        $target = Admin::factory()->create(['name' => 'Original Name', 'role_id' => $heldRole->id, 'status' => 1]);

        $row = ['name' => 'Taken Over', 'email' => $target->email, 'role_name' => $heldRole->name];

        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $manager);

        expect($importer->validateRow($row, 1))->toBeTrue();

        $managerRole->update(['permissions' => ['dashboard', 'data_transfer.imports']]);

        $importer->importBatch(JobTrackBatch::factory()->create([
            'job_track_id' => JobTrack::factory()->create(['action' => Import::ACTION_APPEND]),
            'data'         => [$row],
        ]));

        expect($target->fresh()->name)->toBe('Original Name');
    });

    it('rejects a row that turns an existing role into full access when the importing admin lacks it', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin);

        expect($importer->validateRow([
            'name'            => $this->limitedAdmin->name,
            'email'           => $this->limitedAdmin->email,
            'role_name'       => $this->limitedRole->name,
            'permission_type' => 'all',
        ], 1))->toBeFalse();
    });

    it('lets the importing admin assign a role it holds', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->limitedAdmin);

        expect($importer->validateRow([
            'name'      => 'New User',
            'email'     => 'new-'.uniqid().'@example.com',
            'role_name' => $this->limitedRole->name,
        ], 1))->toBeTrue();
    });

    it('rejects moving, disabling or deleting the last active full-access admin', function (string $action, array $row) {
        $importer = importerFor(UserImporter::class, $action, $this->fullAdmin);

        expect($importer->validateRow([
            'name'      => $this->fullAdmin->name,
            'email'     => $this->fullAdmin->email,
            'role_name' => $this->fullRole->name,
            ...array_map(fn ($value) => $value === ':limited' ? $this->limitedRole->name : $value, $row),
        ], 1))->toBeFalse();
    })->with([
        'moved to a custom role' => [Import::ACTION_APPEND, ['role_name' => ':limited']],
        'disabled'               => [Import::ACTION_APPEND, ['status' => 'inactive']],
        'deleted'                => [Import::ACTION_DELETE, []],
    ]);

    it('rejects moving the last full-access admin onto a role the same file demotes, in either row order', function (bool $moveFirst) {
        $spareFullRole = Role::factory()->create(['name' => 'Spare '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
        $other = Admin::factory()->create(['role_id' => $this->limitedRole->id, 'status' => 1]);

        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        $demoteRow = [
            'name'            => $other->name,
            'email'           => $other->email,
            'role_name'       => $spareFullRole->name,
            'permission_type' => 'custom',
        ];
        $moveRow = [
            'name'      => $this->fullAdmin->name,
            'email'     => $this->fullAdmin->email,
            'role_name' => $spareFullRole->name,
        ];

        [$first, $second] = $moveFirst ? [$moveRow, $demoteRow] : [$demoteRow, $moveRow];

        $results = [$importer->validateRow($first, 1), $importer->validateRow($second, 2)];

        expect($results)->toContain(false);
    })->with(['demote first' => false, 'move first' => true]);

    it('rejects demoting the last active full-access role through a user row', function () {
        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow([
            'name'            => $this->fullAdmin->name,
            'email'           => $this->fullAdmin->email,
            'role_name'       => $this->fullRole->name,
            'permission_type' => 'custom',
            'permissions'     => 'dashboard',
        ], 1))->toBeFalse();
    });

    it('allows changing a full-access admin while another active admin keeps full access', function () {
        Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);

        $importer = importerFor(UserImporter::class, Import::ACTION_APPEND, $this->fullAdmin);

        expect($importer->validateRow([
            'name'      => $this->fullAdmin->name,
            'email'     => $this->fullAdmin->email,
            'role_name' => $this->limitedRole->name,
        ], 1))->toBeTrue();
    });
});

it('keeps the importer chosen when the job was created even if the profile entity type changes', function () {
    $jobInstance = JobInstances::factory()->create(['type' => 'import', 'action' => Import::ACTION_APPEND, 'entity_type' => 'users']);

    $jobTrack = JobTrack::factory()->create([
        'action'           => Import::ACTION_APPEND,
        'job_instances_id' => $jobInstance->id,
        'meta'             => $jobInstance->toArray(),
    ]);

    $jobInstance->update(['entity_type' => 'roles']);

    $importHelper = app(Import::class)->setImport($jobTrack->fresh());

    expect((fn () => $this->pinnedEntityType())->call($importHelper))->toBe('users');
});

describe('stored role access', function () {
    beforeEach(function () {
        $this->roleManager = Admin::factory()->create(['status' => 1, 'role_id' => Role::factory()->create([
            'name'            => 'Role manager '.uniqid(),
            'permission_type' => 'custom',
            'permissions'     => ['dashboard', 'data_transfer.imports', 'settings.roles.create', 'settings.roles.edit', 'settings.roles.delete', 'settings.users.users.create', 'settings.users.users.edit'],
        ])->id]);
    });

    it('rejects a role row that rewrites or deletes a role wider than the importing admin', function (array $stored) {
        $wide = Role::factory()->create(['name' => 'Wide '.uniqid(), ...$stored]);
        $row = ['name' => $wide->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'];

        expect(importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->roleManager)->validateRow($row, 1))->toBeFalse()
            ->and(importerFor(RoleImporter::class, Import::ACTION_DELETE, $this->roleManager)->validateRow(['name' => $wide->name], 1))->toBeFalse();

        writeImportBatch(RoleImporter::class, Import::ACTION_APPEND, [$row], $this->roleManager);
        writeImportBatch(RoleImporter::class, Import::ACTION_DELETE, [['name' => $wide->name]], $this->roleManager);

        expect(Role::find($wide->id))->not->toBeNull()
            ->and(Role::find($wide->id)->permission_type)->toBe($stored['permission_type']);
    })->with([
        'full access'         => [['permission_type' => 'all', 'permissions' => null]],
        'wider custom access' => [['permission_type' => 'custom', 'permissions' => ['dashboard', 'settings.users']]],
    ]);

    it('rejects a user row that rewrites a role wider than the importing admin', function () {
        $wide = Role::factory()->create(['name' => 'Wide '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard', 'settings.users']]);
        $row = ['email' => 'wide-'.uniqid().'@example.com', 'name' => 'Wide user', 'status' => 1, 'ui_locale' => 'en_US', 'timezone' => 'UTC', 'role_name' => $wide->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'];

        expect(importerFor(UserImporter::class, Import::ACTION_APPEND, $this->roleManager)->validateRow($row, 1))->toBeFalse();

        writeImportBatch(UserImporter::class, Import::ACTION_APPEND, [$row], $this->roleManager);

        expect(Role::find($wide->id)->permissions)->toBe(['dashboard', 'settings.users']);
    });

    it('still lets the importing admin rewrite and delete a role within its own access', function () {
        $narrow = Role::factory()->create(['name' => 'Narrow '.uniqid(), 'permission_type' => 'custom', 'permissions' => ['dashboard', 'settings.roles.edit']]);

        expect(importerFor(RoleImporter::class, Import::ACTION_APPEND, $this->roleManager)->validateRow(['name' => $narrow->name, 'permission_type' => 'custom', 'permissions' => 'dashboard'], 1))->toBeTrue()
            ->and(importerFor(RoleImporter::class, Import::ACTION_DELETE, $this->roleManager)->validateRow(['name' => $narrow->name], 1))->toBeTrue();
    });
});
