<?php

use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;
use Webkul\Core\Models\Locale;
use Webkul\HistoryControl\Interfaces\PresentableHistoryInterface;
use Webkul\HistoryControl\Presenters\BooleanPresenter;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

/**
 * Mirrors Admin with the cast proposed for issue #1241, so the effect of the cast on what
 * gets written to the audit trail can be measured without editing the shipped model.
 */
class StatusCastAdmin extends Admin
{
    protected $table = 'admins';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'       => 'boolean',
            'use_gravatar' => 'boolean',
        ];
    }
}

function auditsTable(): string
{
    return (new Audit)->getTable();
}

function rawAdminAudits(int $adminId, string $type = Admin::class): array
{
    return DB::table(auditsTable())
        ->where('auditable_type', $type)
        ->where('auditable_id', $adminId)
        ->where('event', 'updated')
        ->orderBy('id')
        ->get(['id', 'version_id', 'old_values', 'new_values'])
        ->all();
}

function forgetAdminAudits(int $adminId): void
{
    DB::table(auditsTable())
        ->whereIn('auditable_type', [Admin::class, StatusCastAdmin::class])
        ->where('auditable_id', $adminId)
        ->delete();
}

function makeEditableAdmin(array $overrides = []): Admin
{
    $admin = Admin::factory()->create(array_merge([
        'name'         => 'History Subject',
        'role_id'      => Role::factory()->create(['permission_type' => 'all'])->id,
        'ui_locale_id' => Locale::where('code', 'en_US')->first()?->id,
        'timezone'     => 'UTC',
        'status'       => 1,
        'use_gravatar' => true,
    ], $overrides));

    forgetAdminAudits($admin->id);

    return $admin->fresh();
}

function submitAdminUpdate(object $test, Admin $admin, array $overrides = [])
{
    return $test->put(route('admin.settings.users.update'), array_merge([
        'id'           => $admin->id,
        'name'         => 'History Subject',
        'email'        => $admin->email,
        'password'     => '',
        'role_id'      => $admin->role_id,
        'ui_locale_id' => $admin->ui_locale_id,
        'timezone'     => 'UTC',
        'status'       => 1,
        'use_gravatar' => 1,
    ], $overrides));
}

describe('Admin cast coverage', function () {
    it('casts use_gravatar to boolean but leaves status uncast', function () {
        $casts = (new Admin)->getCasts();

        expect($casts)->toHaveKey('use_gravatar')
            ->and($casts['use_gravatar'])->toBe('boolean')
            ->and($casts)->not->toHaveKey('status');
    });

    it('reads status back as an integer and use_gravatar as a boolean', function () {
        $admin = makeEditableAdmin();

        expect($admin->status)->toBeInt()
            ->and($admin->use_gravatar)->toBeBool();
    });
});

describe('stored audit value types', function () {
    it('stores the status old value as an integer and the new value as a boolean', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin();

        submitAdminUpdate($this, $admin, ['status' => 0])->assertSuccessful();

        $audit = collect(rawAdminAudits($admin->id))->first(
            fn (object $row): bool => array_key_exists('status', json_decode($row->new_values, true))
        );

        expect($audit)->not->toBeNull()
            ->and($audit->old_values)->toContain('"status":1')
            ->and($audit->new_values)->toContain('"status":false');

        expect(json_decode($audit->old_values, true)['status'])->toBeInt()
            ->and(json_decode($audit->new_values, true)['status'])->toBeBool();
    });

    it('stores the same mismatch for use_gravatar even though it is cast to boolean', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin();

        submitAdminUpdate($this, $admin, ['use_gravatar' => 0])->assertSuccessful();

        $audit = collect(rawAdminAudits($admin->id))->first(
            fn (object $row): bool => array_key_exists('use_gravatar', json_decode($row->new_values, true))
        );

        expect($audit)->not->toBeNull()
            ->and($audit->old_values)->toContain('"use_gravatar":1')
            ->and($audit->new_values)->toContain('"use_gravatar":false');

        expect(json_decode($audit->old_values, true)['use_gravatar'])->toBeInt()
            ->and(json_decode($audit->new_values, true)['use_gravatar'])->toBeBool();
    });

    it('keeps writing an integer old value against a boolean new value once status is cast', function () {
        $admin = makeEditableAdmin();

        $casted = StatusCastAdmin::find($admin->id);
        $casted->status = false;
        $casted->save();

        $audit = collect(rawAdminAudits($admin->id, StatusCastAdmin::class))->first(
            fn (object $row): bool => array_key_exists('status', json_decode($row->new_values, true))
        );

        expect($audit)->not->toBeNull()
            ->and($audit->old_values)->toContain('"status":1')
            ->and($audit->new_values)->toContain('"status":false');

        expect(json_decode($audit->old_values, true)['status'])->toBeInt()
            ->and(json_decode($audit->new_values, true)['status'])->toBeBool();
    });
});

describe('phantom audit rows', function () {
    it('records a status audit for a save that changes nothing', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin();

        submitAdminUpdate($this, $admin)->assertSuccessful();

        $audits = rawAdminAudits($admin->id);

        expect($audits)->toHaveCount(1)
            ->and(json_decode($audits[0]->old_values, true))->toBe(['status' => 1])
            ->and(json_decode($audits[0]->new_values, true))->toBe(['status' => true])
            ->and($audits[0]->new_values)->not->toContain('use_gravatar');

        expect($admin->fresh()->status)->toBe(1);
    });

    it('records nothing when status is cast and nothing actually changed', function () {
        $admin = makeEditableAdmin();

        $casted = StatusCastAdmin::find($admin->id);
        $casted->status = true;
        $casted->use_gravatar = true;
        $casted->save();

        expect(rawAdminAudits($admin->id, StatusCastAdmin::class))->toBeEmpty();
    });
});

describe('history preview payload', function () {
    it('presents both sides of status as booleans', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin();

        submitAdminUpdate($this, $admin)->assertSuccessful();

        $audit = rawAdminAudits($admin->id)[0];

        $response = $this->getJson(route('admin.history.version.view', [
            'entity'    => 'admin',
            'id'        => $admin->id,
            'versionId' => $audit->version_id,
        ]));

        $response->assertSuccessful();

        $entry = $response->json('versionHistory.status');

        expect($entry)->not->toBeNull()
            ->and($entry['old'])->toBeTrue()
            ->and($entry['old'])->toBeBool()
            ->and($entry['new'])->toBeTrue()
            ->and($entry['new'])->toBeBool();

        expect($response->getContent())->toContain('"old":true')
            ->and($response->getContent())->not->toContain('"old":1');
    });

    it('presents an older row written before the fix as booleans too', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin();

        submitAdminUpdate($this, $admin)->assertSuccessful();

        $audit = rawAdminAudits($admin->id)[0];

        DB::table('audits')->where('id', $audit->id)->update([
            'old_values' => json_encode(['status' => 1, 'use_gravatar' => 0]),
            'new_values' => json_encode(['status' => true, 'use_gravatar' => true]),
        ]);

        $response = $this->getJson(route('admin.history.version.view', [
            'entity'    => 'admin',
            'id'        => $admin->id,
            'versionId' => $audit->version_id,
        ]));

        $response->assertSuccessful();

        expect($response->json('versionHistory.status.old'))->toBeBool()
            ->and($response->json('versionHistory.use_gravatar.new'))->toBeBool()
            ->and($response->getContent())->not->toContain('"old":1');
    });

    it('drops the entry entirely when both sides are falsy', function () {
        $this->loginAsAdmin();

        $admin = makeEditableAdmin(['status' => 0]);

        submitAdminUpdate($this, $admin, ['status' => 0])->assertSuccessful();

        $audit = rawAdminAudits($admin->id)[0];

        expect(json_decode($audit->old_values, true))->toBe(['status' => 0])
            ->and(json_decode($audit->new_values, true))->toBe(['status' => false]);

        $response = $this->getJson(route('admin.history.version.view', [
            'entity'    => 'admin',
            'id'        => $admin->id,
            'versionId' => $audit->version_id,
        ]));

        $response->assertSuccessful();

        expect($response->json('versionHistory'))->not->toHaveKey('status');
    });
});

describe('formatting layer', function () {
    it('registers a boolean presenter for both flag columns', function () {
        expect(class_implements(Admin::class))
            ->toHaveKey(PresentableHistoryInterface::class);

        expect(Admin::getPresenters())
            ->toBe([
                'status'       => BooleanPresenter::class,
                'use_gravatar' => BooleanPresenter::class,
            ]);
    });
});
