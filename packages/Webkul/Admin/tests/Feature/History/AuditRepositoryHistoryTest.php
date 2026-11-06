<?php

use Illuminate\Support\Facades\DB;
use Webkul\HistoryControl\Repositories\AuditRepository;
use Webkul\User\Models\Admin;

function insertHistoryAudit(string $tag, int $historyId, int $version, ?int $userId = null): int
{
    return DB::table('audits')->insertGetId([
        'user_type'      => $userId ? Admin::class : null,
        'user_id'        => $userId,
        'event'          => $version === 1 ? 'created' : 'updated',
        'auditable_type' => 'records',
        'auditable_id'   => $historyId,
        'old_values'     => json_encode(['name' => 'v'.($version - 1)]),
        'new_values'     => json_encode(['name' => 'v'.$version]),
        'url'            => 'console',
        'ip_address'     => '127.0.0.1',
        'user_agent'     => 'pest',
        'tags'           => $tag,
        'history_id'     => $historyId,
        'version_id'     => $version,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
}

it('returns the latest audit entries of one history entity, newest first, with the admin name', function () {
    $admin = Admin::factory()->create(['name' => 'History Reader']);

    $first = insertHistoryAudit('record_history_test', 9101, 1, $admin->id);
    $second = insertHistoryAudit('record_history_test', 9101, 2, $admin->id);
    $third = insertHistoryAudit('record_history_test', 9101, 3);
    insertHistoryAudit('record_history_test', 9102, 1, $admin->id);
    insertHistoryAudit('other_history_test', 9101, 1, $admin->id);

    $entries = app(AuditRepository::class)->latestForHistory('record_history_test', 9101, 2);

    expect($entries->pluck('id')->all())->toBe([$third, $second])
        ->and($entries->first()->user)->toBeNull()
        ->and($entries->last()->user)->toBe('History Reader')
        ->and($entries->last()->new_values)->toBe(['name' => 'v2']);

    expect(app(AuditRepository::class)->latestForHistory('record_history_test', 9101)->pluck('id')->all())
        ->toBe([$third, $second, $first]);
});

it('deletes only the audit entries of the given history entities', function () {
    insertHistoryAudit('record_history_test', 9201, 1);
    insertHistoryAudit('record_history_test', 9202, 1);
    $kept = insertHistoryAudit('record_history_test', 9203, 1);
    $otherTag = insertHistoryAudit('other_history_test', 9201, 1);

    app(AuditRepository::class)->deleteForHistory('record_history_test', [9201, 9202]);

    expect(DB::table('audits')->whereIn('history_id', [9201, 9202, 9203])->orderBy('id')->pluck('id')->all())
        ->toBe([$kept, $otherTag]);
});
