<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;

it('keeps the password hash out of the audit trail', function () {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $admin->update(['password' => Hash::make('another-password')]);

    $values = DB::table('audits')
        ->where('auditable_type', Admin::class)
        ->where('auditable_id', $admin->id)
        ->pluck('new_values')
        ->concat(
            DB::table('audits')
                ->where('auditable_type', Admin::class)
                ->where('auditable_id', $admin->id)
                ->pluck('old_values')
        );

    foreach ($values as $value) {
        expect(json_decode((string) $value, true) ?? [])
            ->not->toHaveKey('password')
            ->not->toHaveKey('api_token')
            ->not->toHaveKey('remember_token');
    }
});

it('writes no audit at all when only the password changes', function () {
    $admin = Admin::factory()->create(['password' => Hash::make('password')]);

    $before = DB::table('audits')
        ->where('auditable_type', Admin::class)
        ->where('auditable_id', $admin->id)
        ->count();

    $this->travel(2)->seconds();

    $admin->update(['password' => Hash::make('another-password')]);

    expect(
        DB::table('audits')
            ->where('auditable_type', Admin::class)
            ->where('auditable_id', $admin->id)
            ->count()
    )->toBe($before);
});

it('still audits a change that touches an auditable column', function () {
    $admin = Admin::factory()->create(['name' => 'Before']);

    $this->travel(2)->seconds();

    $admin->update(['name' => 'After', 'password' => Hash::make('another-password')]);

    $values = DB::table('audits')
        ->where('auditable_type', Admin::class)
        ->where('auditable_id', $admin->id)
        ->latest('id')
        ->value('new_values');

    expect(json_decode((string) $values, true))
        ->toHaveKey('name')
        ->not->toHaveKey('password');
});

it('is not ready for auditing when the touched columns are excluded or timestamps', function () {
    $admin = Admin::factory()->create();

    $admin->password = Hash::make('another-password');
    $admin->updated_at = now()->addMinute();

    $admin->setAuditEvent('updated');

    expect($admin->readyForAuditing())->toBeFalse();
});

it('is ready for auditing when an auditable column is touched alongside them', function () {
    $admin = Admin::factory()->create();

    $admin->name = 'After';
    $admin->password = Hash::make('another-password');
    $admin->updated_at = now()->addMinute();

    $admin->setAuditEvent('updated');

    expect($admin->readyForAuditing())->toBeTrue();
});
