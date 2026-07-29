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
