<?php

namespace Webkul\Admin\Http\Controllers\User;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\User\Models\Admin;

class AvatarController extends Controller
{
    /**
     * Proxy gravatar image through local app domain.
     *
     * The upstream fetch is cached (misses included) by the model so a listing that renders one
     * avatar per row does not trigger a synchronous gravatar round-trip per request. The short
     * revalidating cache header mirrors gravatar.com's own, so a replaced avatar is not pinned in
     * the browser long after the server side has picked it up.
     */
    public function gravatar(Request $request, string $hash): Response
    {
        if (! preg_match('/^[a-f0-9]{32}$/', $hash)) {
            abort(404);
        }

        $payload = Admin::gravatarPayload($hash);

        if (! $payload['found']) {
            return response('', 404)
                ->header('Cache-Control', 'public, max-age='.Admin::GRAVATAR_MISS_TTL);
        }

        $response = response($payload['body'])
            ->header('Content-Type', $payload['content_type'])
            ->header('Cache-Control', 'public, max-age='.Admin::GRAVATAR_FRESH_SECONDS.', must-revalidate');

        if ($lastModified = $this->parseHttpDate($payload['last_modified'] ?? null)) {
            $response->setLastModified($lastModified);

            $response->isNotModified($request);
        }

        return $response;
    }

    private function parseHttpDate(?string $value): ?DateTimeImmutable
    {
        if (! $value) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeInterface::RFC7231, $value, new DateTimeZone('GMT'));

        return $date ?: null;
    }
}
