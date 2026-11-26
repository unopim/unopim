<?php

use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;
use Webkul\User\Repositories\AdminRepository;

/**
 * Store an agent memory and return its id.
 */
function storeAgentMemory(string $scope, ?int $userId, string $key): int
{
    return DB::table('ai_agent_memories')->insertGetId([
        'scope'      => $scope,
        'key'        => $key,
        'user_id'    => $userId,
        'value'      => 'Remembered fact',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('deletes the memories an admin owns when the admin is deleted, so they never turn into shared memories', function () {
    $author = Admin::factory()->create();
    $colleague = Admin::factory()->create();

    $ownPrivate = storeAgentMemory('user', $author->id, 'private_note');
    $ownFeedback = storeAgentMemory('catalog', $author->id, 'content_style_preference');
    $colleagueMemory = storeAgentMemory('user', $colleague->id, 'colleague_note');
    $sharedMemory = storeAgentMemory('global', null, 'brand_tone');

    resolve(AdminRepository::class)->delete($author->id);

    expect(DB::table('ai_agent_memories')->whereIn('id', [$ownPrivate, $ownFeedback])->exists())->toBeFalse()
        ->and(DB::table('ai_agent_memories')->whereNull('user_id')->pluck('id')->all())->toBe([$sharedMemory])
        ->and(DB::table('ai_agent_memories')->where('id', $colleagueMemory)->value('user_id'))->toBe($colleague->id);
});
