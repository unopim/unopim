<?php

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Listeners\Installer;
use Webkul\User\Models\Admin;

function trackerPayloadFor(?string $adminTimezone, string $appTimezone = 'UTC'): array
{
    config(['app.timezone' => $appTimezone]);

    DB::table('admins')->delete();

    if ($adminTimezone !== null) {
        Admin::factory()->create(['timezone' => $adminTimezone]);
    }

    $history = [];

    $stack = HandlerStack::create(new MockHandler([new Response(200)]));
    $stack->push(Middleware::history($history));

    app()->instance(Client::class, new Client(['handler' => $stack]));

    app(Installer::class)->installed();

    expect($history)->toHaveCount(1);

    return json_decode((string) $history[0]['request']->getBody(), true);
}

describe('install tracker payload country_code', function () {
    it('derives the country from the admin timezone', function () {
        expect(trackerPayloadFor('Europe/Berlin')['country_code'])->toBe('DE')
            ->and(trackerPayloadFor('America/New_York')['country_code'])->toBe('US')
            ->and(trackerPayloadFor('Asia/Kolkata')['country_code'])->toBe('IN');
    });

    it('falls back to the app timezone when no admin exists', function () {
        expect(trackerPayloadFor(null, 'Europe/London')['country_code'])->toBe('GB');
    });

    it('sends no country instead of a hardcoded IN when the timezone has no location', function () {
        expect(trackerPayloadFor('UTC')['country_code'])->toBeNull();
    });

    it('still sends the admin email and name', function () {
        $payload = trackerPayloadFor('Europe/Berlin');

        $admin = Admin::first();

        expect($payload['email'])->toBe($admin->email)
            ->and($payload['name'])->toBe($admin->name);
    });
});
