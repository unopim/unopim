<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Models\Admin;

class AvatarController extends Controller
{
    /**
     * Proxy gravatar image through local app domain.
     *
     * The upstream fetch is cached (misses included) by the model so a listing that renders one
     * avatar per row does not trigger a synchronous gravatar round-trip per request.
     */
    public function gravatar(string $hash): Response
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $hash)) {
            abort(404);
        }

        $payload = Admin::gravatarPayload($hash);

        /**
         * Most admins have no gravatar, and a listing asks for one per row on
         * every page change. A bare `abort(404)` carries no cache headers, so
         * the browser re-asked for each missing avatar every single time; answer
         * with a cacheable 404 instead so it asks once and then falls back to
         * the initials avatar on its own.
         */
        if (! $payload['found']) {
            return response('', 404)
                ->header('Cache-Control', 'public, max-age=3600');
        }

        return response($payload['body'])
            ->header('Content-Type', $payload['content_type'])
            ->header('Cache-Control', 'public, max-age=86400');
    }
}
