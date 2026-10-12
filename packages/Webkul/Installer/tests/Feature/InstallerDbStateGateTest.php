<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config(['app.url' => 'http://localhost']);
    URL::forceRootUrl('http://localhost');

    // Completed install whose storage marker was lost but DB flag persists.
    $this->marker = storage_path('installed');
    $this->markerExisted = file_exists($this->marker);
    $this->markerContents = $this->markerExisted ? file_get_contents($this->marker) : null;

    if ($this->markerExisted) {
        unlink($this->marker);
    }

    DB::table('core_config')->updateOrInsert(['code' => 'installer.installed'], ['value' => '1']);
});

afterEach(function () {
    if ($this->markerExisted) {
        file_put_contents($this->marker, $this->markerContents);
    } elseif (file_exists($this->marker)) {
        unlink($this->marker);
    }

    DB::table('core_config')->where('code', 'installer.installed')->delete();
});

it('does not let an unauthenticated request overwrite the super-admin when the marker is missing but the DB is installed', function () {
    $original = DB::table('admins')->where('id', 1)->first();

    expect($original)->not->toBeNull();

    $this->postJson('/install/api/admin-config-setup', [
        'admin'    => 'attacker',
        'email'    => 'evil@example.test',
        'password' => 'Pwned#12345',
        'timezone' => 'UTC',
        'locale'   => 'en_US',
    ]);

    $after = DB::table('admins')->where('id', 1)->first();

    expect($after->email)->toBe($original->email)
        ->and($after->password)->toBe($original->password);

    $this->assertDatabaseMissing('admins', ['email' => 'evil@example.test']);
});

it('does not let an unauthenticated request re-run migrations when the marker is missing but the DB is installed', function () {
    $response = $this->postJson('/install/api/run-migration');

    expect($response->getStatusCode())->not->toBe(200);

    expect(DB::table('admins')->where('id', 1)->exists())->toBeTrue();
});
