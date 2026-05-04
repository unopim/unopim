<?php
/*
 * Idempotent seed for DamWebdav docs screenshots. Run from unopim repo root:
 *   php tests/e2e-pw/tests/docs-screenshots/seed.php
 *
 * Outputs a single JSON line on stdout: {"credentialId":N,"profileId":N,"remoteId":N,"trashId":N}
 */

require __DIR__.'/../../../../vendor/autoload.php';

$app = require __DIR__.'/../../../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$adminId = DB::table('admins')->value('id') ?? 1;
$dirId   = DB::table('dam_directories')->value('id') ?? 1;

$credentialId = DB::table('dam_webdav_credentials')->where('username', 'docs-demo')->value('id');
if (! $credentialId) {
    $credentialId = DB::table('dam_webdav_credentials')->insertGetId([
        'user_id'    => $adminId,
        'label'      => 'Docs Demo User',
        'username'   => 'docs-demo',
        'token_hash' => Hash::make('StrongPass!234'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

$profileId = DB::table('dam_webdav_sync_profiles')->where('name', 'Docs Demo Profile')->value('id');
if (! $profileId) {
    $profileId = DB::table('dam_webdav_sync_profiles')->insertGetId([
        'name'              => 'Docs Demo Profile',
        'direction'         => 'two_way',
        'root_directory_id' => $dirId,
        'allow_create'      => 1,
        'allow_update'      => 1,
        'allow_delete'      => 1,
        'delete_mode'       => 'trash',
        'status'            => 1,
        'created_at'        => now(),
        'updated_at'        => now(),
    ]);
}

if (DB::table('dam_webdav_credentials')->where('id', $credentialId)->whereNull('profile_id')->exists()) {
    DB::table('dam_webdav_credentials')->where('id', $credentialId)->update(['profile_id' => $profileId]);
}

$remoteId = DB::table('dam_webdav_remote_sources')->where('name', 'Docs Demo Remote')->value('id');
if (! $remoteId) {
    $remoteId = DB::table('dam_webdav_remote_sources')->insertGetId([
        'name'               => 'Docs Demo Remote',
        'endpoint_url'       => 'https://demo.nextcloud.example/remote.php/dav/files/demo/',
        'auth_username'      => 'demo',
        'auth_token'         => 'demo',
        'auth_type'          => 'basic',
        'remote_path'        => '/',
        'local_directory_id' => $dirId,
        'direction'          => 'pull_only',
        'interval_minutes'   => 360,
        'last_status'        => 'success',
        'last_message'       => 'Connected — 12 entries listed.',
        'last_run_at'        => now()->subMinutes(15),
        'is_running'         => 0,
        'status'             => 1,
        'created_at'         => now(),
        'updated_at'         => now(),
    ]);
}

$assetId = DB::table('dam_assets')->value('id');
if ($assetId) {
    $eventCount = DB::table('dam_webdav_sync_events')->where('credential_id', $credentialId)->count();
    if ($eventCount === 0) {
        $now = now();
        DB::table('dam_webdav_sync_events')->insert([
            ['credential_id' => $credentialId, 'profile_id' => $profileId, 'remote_source_id' => null, 'verb' => 'PUT',      'path' => '/Campaigns/SS26/hero.jpg',  'bytes' => 482113, 'status' => 'success',  'error_message' => null,                       'created_at' => $now->copy()->subMinutes(2)],
            ['credential_id' => $credentialId, 'profile_id' => $profileId, 'remote_source_id' => null, 'verb' => 'PROPFIND', 'path' => '/Campaigns/SS26',           'bytes' => 0,      'status' => 'success',  'error_message' => null,                       'created_at' => $now->copy()->subMinutes(5)],
            ['credential_id' => $credentialId, 'profile_id' => $profileId, 'remote_source_id' => null, 'verb' => 'DELETE',   'path' => '/Drafts/old.psd',           'bytes' => 0,      'status' => 'success',  'error_message' => null,                       'created_at' => $now->copy()->subMinutes(8)],
            ['credential_id' => $credentialId, 'profile_id' => $profileId, 'remote_source_id' => null, 'verb' => 'PUT',      'path' => '/Drafts/conflict.png',      'bytes' => 0,      'status' => 'conflict', 'error_message' => 'Remote ETag changed',     'created_at' => $now->copy()->subMinutes(10)],
        ]);
    }
}

$trashId = DB::table('dam_webdav_trash')->where('original_path', '/Drafts/old.psd')->value('id');
if (! $trashId) {
    $trashId = DB::table('dam_webdav_trash')->insertGetId([
        'asset_id'              => $assetId,
        'original_directory_id' => $dirId,
        'original_path'         => '/Drafts/old.psd',
        'trash_path'            => '.trash/'.uniqid('asset_').'/old.psd',
        'file_name'             => 'old.psd',
        'mime_type'             => 'image/vnd.adobe.photoshop',
        'bytes'                 => 5_241_882,
        'deleted_by'            => $adminId,
        'credential_id'         => $credentialId,
        'deleted_at'            => now()->subDays(2),
        'purge_after'           => now()->addDays(28),
    ]);
}

echo json_encode([
    'credentialId' => (int) $credentialId,
    'profileId'    => (int) $profileId,
    'remoteId'     => (int) $remoteId,
    'trashId'      => (int) $trashId,
])."\n";
