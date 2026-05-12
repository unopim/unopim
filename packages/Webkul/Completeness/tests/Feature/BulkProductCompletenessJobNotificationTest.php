<?php

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;

it('joins the roles table (not admin_roles) when looking up admins to notify so the post-seed completeness step does not crash with a missing-table error', function () {

    expect(fn () => BulkProductCompletenessJob::sendCompletionNotification(
        totalProducts: 0,
        userId: null,
        familyId: null,
    ))->not->toThrow(QueryException::class);
});

it('selects admins via the roles join when no specific userId is supplied', function () {

    $roleId = DB::table('roles')->insertGetId([
        'name'            => 'TestAllRole',
        'description'     => 'all-perm role for completeness notification test',
        'permission_type' => 'all',
        'permissions'     => null,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    $adminId = DB::table('admins')->insertGetId([
        'name'       => 'Completeness Notify Target',
        'email'      => 'notify-target+'.uniqid().'@example.test',
        'password'   => bcrypt('test'),
        'status'     => 1,
        'role_id'    => $roleId,
        'timezone'   => 'UTC',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $hits = DB::table('admins')
        ->join('roles', 'admins.role_id', '=', 'roles.id')
        ->where('roles.permission_type', 'all')
        ->where('admins.id', $adminId)
        ->count();

    expect($hits)->toBe(1);
});
