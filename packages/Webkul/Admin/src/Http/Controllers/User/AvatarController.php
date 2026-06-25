<?php

namespace Webkul\Admin\Http\Controllers\User;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Webkul\Admin\Http\Controllers\Controller;

class AvatarController extends Controller
{
    /**
     * Proxy gravatar image through local app domain.
     */
    public function gravatar(string $hash): Response
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $hash)) {
            abort(404);
        }

        $gravatarUrl = "https://gravatar.com/avatar/{$hash}?s=200&d=404";

        try {
            $response = Http::timeout(4)
                ->withHeaders([
                    'User-Agent' => 'UnoPim Avatar Proxy',
                    'Accept'     => 'image/*',
                ])
                ->get($gravatarUrl);
        } catch (ConnectionException $exception) {
            abort(404);
        }

        if (! $response->successful()) {
            abort(404);
        }

        return response($response->body())
            ->header('Content-Type', $response->header('Content-Type', 'image/png'))
            ->header('Cache-Control', 'public, max-age=300');
    }
}
