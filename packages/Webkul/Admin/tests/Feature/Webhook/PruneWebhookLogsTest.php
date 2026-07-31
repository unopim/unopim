<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

it('prunes only logs older than the retention window', function () {
    DB::table('webhook_logs')->insert([
        [
            'sku'        => 'OLD-LOG',
            'user'       => 'prune-tester',
            'status'     => 1,
            'http_code'  => 200,
            'created_at' => now()->subDays(40),
            'updated_at' => now()->subDays(40),
        ],
        [
            'sku'        => 'FRESH-LOG',
            'user'       => 'prune-tester',
            'status'     => 1,
            'http_code'  => 200,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ],
    ]);

    Artisan::call('webhook:logs:prune', ['--days' => 30]);

    $remaining = DB::table('webhook_logs')->where('user', 'prune-tester')->pluck('sku')->all();

    expect($remaining)->toBe(['FRESH-LOG']);
});

it('does nothing when retention is disabled', function () {
    DB::table('webhook_logs')->insert([
        'sku'        => 'KEEP-LOG',
        'user'       => 'prune-tester',
        'status'     => 1,
        'http_code'  => 200,
        'created_at' => now()->subDays(400),
        'updated_at' => now()->subDays(400),
    ]);

    Artisan::call('webhook:logs:prune', ['--days' => 0]);

    expect(DB::table('webhook_logs')->where('user', 'prune-tester')->count())->toBe(1);
});
