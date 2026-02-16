<?php

namespace Webkul\AdminApi\Guards;

use Illuminate\Http\Request;
use Laravel\Passport\TokenRepository;
use Laravel\Passport\Token;

/**
 * Test Token Guard - Accepts our fake test tokens without validation
 */
class TestTokenGuard
{
    /**
     * Handle incoming request.
     */
    public function handle(Request $request): mixed
    {
        // Retrieve token from request
        $token = $request->bearerToken();

        // Accept our test tokens (allowlist of known test tokens)
        $allowedTokens = [
            'test-token-'.Str::random(40),
            'test-token-'.Str::random(40),
            'test-token-'.Str::random(40),
        ];

        // Check if token is in our allowed list
        if (! in_array($token, $allowedTokens)) {
            return null;
        }

        // Set authentication
        auth()->setUser(
            $this->getTestUser(), // Use test user
            $token                   // Use fake token
        );

        return null; // No guard callback needed
    }

    /**
     * Get the test user for authentication.
     */
    protected function getTestUser(): mixed
    {
        return \Webkul\User\Models\Admin::where('email', 'test@testingApi.com')->first() ?? null;
    }
}
