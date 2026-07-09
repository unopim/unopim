<?php

namespace Webkul\Admin\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * Shared response contract for admin resource controllers. A store/update/destroy
 * action calls one of these helpers instead of hand-building a redirect or JSON
 * body, so every resource speaks the same shape: JSON ({message, redirect_url})
 * for ajax/API callers and the classic flash + redirect for full-page requests.
 */
trait ProvidesResourceResponses
{
    /**
     * Successful create/update. Pass a redirect URL when the caller should move
     * on (e.g. create → index); omit it to stay on the current page (edit).
     */
    protected function respondSaved(string $message, ?string $redirectUrl = null): JsonResponse|RedirectResponse
    {
        if (request()->wantsJson()) {
            $payload = ['message' => $message];

            if ($redirectUrl !== null) {
                $payload['redirect_url'] = $redirectUrl;
            }

            return new JsonResponse($payload);
        }

        session()->flash('success', $message);

        return $redirectUrl !== null ? redirect()->to($redirectUrl) : back();
    }

    /**
     * A handled failure (guard rejected, dependency in use). Defaults to 422 so
     * the shared ajax form surfaces it as an error flash without navigating.
     */
    protected function respondError(
        string $message,
        int $status = JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
        ?string $redirectUrl = null
    ): JsonResponse|RedirectResponse {
        if (request()->wantsJson()) {
            return new JsonResponse(['message' => $message], $status);
        }

        session()->flash('error', $message);

        return $redirectUrl !== null ? redirect()->to($redirectUrl) : back()->withInput();
    }

    /**
     * Successful destroy. Delete actions are always ajax (datagrid row action),
     * so this is JSON-only.
     */
    protected function respondDeleted(string $message): JsonResponse
    {
        return new JsonResponse(['message' => $message]);
    }
}
