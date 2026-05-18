<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\URL;
use Webkul\Installer\Helpers\DatabaseManager;
use Webkul\Installer\Helpers\EnvironmentManager;

beforeEach(function () {
    // The 419 itself is asserted indirectly: if Artisan::call('key:generate')
    // never fires when APP_KEY is set, APP_KEY cannot rotate, the encrypted
    // session cookie stays decryptable on the next request, and the CSRF
    // token stays valid. So we exercise the controller through the same path
    // the UI calls (postJson) but bypass CSRF middleware itself — testing
    // VerifyCsrfToken end-to-end would require simulating browser cookie
    // encryption, which is exactly the surface we're protecting and isn't
    // worth re-implementing in a test harness.
    $this->withoutMiddleware();

    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    // Stub EnvironmentManager so the test never mutates the real .env.
    // The real envFileSetup pipeline writes APP_KEY + DB_* values to disk,
    // which would race against parallel workers and the developer's own env.
    app()->instance(EnvironmentManager::class, new class(app(DatabaseManager::class)) extends EnvironmentManager
    {
        public function generateEnv($request)
        {
            // Mirror the real pipeline: would normally write .env then call
            // DatabaseManager::generateKey(). We skip the .env write but
            // still invoke generateKey() so the regression for issue #867
            // (unconditional key rotation rotating the session cipher) is
            // actually exercised through this controller test.
            $this->databaseManager->generateKey();

            return true;
        }
    });
});

describe('UI installer CSRF survives a second env-file-setup submit (issue #867)', function () {
    it('does not rotate APP_KEY on retry when APP_KEY is already set, so the second submit does not 419', function () {
        // Ensure APP_KEY is set before the test — this is the realistic state
        // of any system that has already booted Laravel once.
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        // Spy on Artisan: key:generate must NEVER run when APP_KEY is set.
        // Before the fix this was called on every envFileSetup submit, which
        // rotated the cipher key and silently nuked the session on the next
        // request — surfacing as 419 Page Expired.
        Artisan::shouldReceive('call')->with('key:generate')->never();

        // First submit (simulating "user typed wrong DB creds, env-file-setup
        // returns 200, then run-migration fails downstream").
        $first = $this->postJson('/install/api/env-file-setup', [
            'db_prefix'     => '',
            'db_hostname'   => '127.0.0.1',
            'db_port'       => 3306,
            'db_connection' => 'mysql',
            'db_name'       => 'unopim',
            'db_username'   => 'root',
            'db_password'   => 'wrong',
        ]);

        $first->assertOk();

        // Second submit with corrected creds. Previously this would 419
        // because the first call had rotated APP_KEY, dropping the session.
        $second = $this->postJson('/install/api/env-file-setup', [
            'db_prefix'     => '',
            'db_hostname'   => '127.0.0.1',
            'db_port'       => 3306,
            'db_connection' => 'mysql',
            'db_name'       => 'unopim',
            'db_username'   => 'root',
            'db_password'   => 'correct',
        ]);

        $second->assertOk();
        expect($second->status())->not->toBe(419);
    });
});
