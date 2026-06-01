<?php

namespace Webkul\AppUrlGuard\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Webkul\AppUrlGuard\Concerns\NormalizesUrl;

/**
 * Lightweight, debug-only endpoint used by the warning modal to re-validate
 * APP_URL without a full reload. It re-reads config('app.url') (which reflects
 * the current .env unless the config cache is stale) and compares it to the
 * host the browser is actually on.
 */
class AppUrlGuardController
{
    use NormalizesUrl;

    /**
     * Report whether APP_URL now matches the requesting host.
     */
    public function check(Request $request): JsonResponse
    {
        abort_unless(config('app.debug'), 404);

        $configured = $this->normalize((string) config('app.url'));
        $actual = $this->normalize($request->getSchemeAndHttpHost().$request->getBaseUrl());

        return response()->json([
            'matches'    => $configured === '' || $configured === $actual,
            'configured' => $configured,
            'actual'     => $actual,
        ]);
    }
}
