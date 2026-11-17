<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\Role\Importer as RoleImporter;
use Webkul\DataTransfer\Helpers\Importers\User\Importer as UserImporter;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->fullRole = Role::factory()->create(['name' => 'Full '.uniqid(), 'permission_type' => 'all', 'permissions' => null]);
    $this->limitedRole = Role::factory()->create([
        'name'            => 'Limited '.uniqid(),
        'permission_type' => 'custom',
        'permissions'     => ['dashboard', 'data_transfer.imports'],
    ]);

    Admin::query()->update(['role_id' => $this->limitedRole->id]);
    Role::query()->where('permission_type', 'all')->whereKeyNot($this->fullRole->id)->update(['permission_type' => 'custom']);

    $this->fullAdmin = Admin::factory()->create(['role_id' => $this->fullRole->id, 'status' => 1]);
    $this->limitedAdmin = Admin::factory()->create(['role_id' => $this->limitedRole->id, 'status' => 1]);

    $this->loginAsAdmin($this->fullAdmin);
});

function importerFor(string $importerClass, string $action, ?Admin $initiator): RoleImporter|UserImporter
{
    $importer = app($importerClass);
    $importer->setImport(JobTrack::factory()->create(['action' => $action, 'user_id' => $initiator?->id]));
    $importer->setErrorHelper(app(Error::class));

    (fn () => $importer instanceof RoleImporter ? $this->roleStorage->init() : $this->userStorage->init())->call($importer);

    return $importer;
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
            ->and($importer->validateRow(['name' => 'New '.uniqid(), 'permission_type' => 'custom'], 3))->toBeTrue();
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
        $importer = importerFor(UserImporter::class, $action, null);

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
