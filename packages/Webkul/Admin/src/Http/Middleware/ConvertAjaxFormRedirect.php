<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertAjaxFormRedirect
{
    /**
     * Turn the redirect that a traditional store/update action returns into a
     * JSON payload the shared ajax form (`onAjaxSubmit`) understands, so pages
     * save without a full reload. Only requests that carry the `X-Ajax-Form`
     * header (set exclusively by the ajax form submit) are affected, which
     * keeps every other flow — including existing axios calls — untouched.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            ! $request->headers->has('X-Ajax-Form')
            || ! $response instanceof RedirectResponse
        ) {
            return $response;
        }

        $session = $request->session();

        if (($errors = $this->validationErrors($session->get('errors'))) !== null) {
            $session->forget('errors');

            return new JsonResponse([
                'message' => collect($errors)->flatten()->first(),
                'errors'  => $errors,
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        foreach (['error', 'warning'] as $type) {
            if ($session->has($type)) {
                $message = $session->get($type);

                $session->forget($type);

                return new JsonResponse(['message' => $message], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return $this->successResponse($request, $session, $response->getTargetUrl());
    }

    /**
     * Build the success payload. When the redirect points somewhere else (e.g.
     * create → edit) the URL is handed to the front-end so it navigates once;
     * when it points back to the same page the flash is consumed here and the
     * user stays put.
     */
    protected function successResponse(Request $request, $session, string $target): JsonResponse
    {
        $message = $session->get('success') ?? $session->get('info');

        $payload = $message !== null ? ['message' => $message] : [];

        if ($this->isDifferentLocation($request, $target)) {
            $payload['redirect_url'] = $target;
        } else {
            $session->forget('success');
            $session->forget('info');
        }

        return new JsonResponse($payload);
    }

    /**
     * Extract the default error bag messages from a flashed error store.
     *
     * @return array<string, array<int, string>>|null
     */
    protected function validationErrors($errors): ?array
    {
        if (! $errors || ! method_exists($errors, 'getBag')) {
            return null;
        }

        $bag = $errors->getBag('default');

        return $bag->isEmpty() ? null : $bag->messages();
    }

    protected function isDifferentLocation(Request $request, string $target): bool
    {
        $targetPath = rtrim((string) parse_url($target, PHP_URL_PATH), '/');

        $currentPath = rtrim($request->getPathInfo(), '/');

        return $targetPath !== $currentPath;
    }
}
