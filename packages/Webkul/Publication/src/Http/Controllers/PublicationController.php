<?php

namespace Webkul\Publication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Registry\PublicationTypeRegistry;
use Webkul\Publication\Services\PublicationResolver;

class PublicationController extends Controller
{
    /**
     * Bump when the rendered template's HTML shape changes in a way that must
     * invalidate every previously cached ETag, independent of payload content.
     */
    private const TEMPLATE_VERSION = '1';

    public function __construct(
        private readonly PublicationResolver $resolver,
        private readonly PublicationTypeRegistry $registry,
    ) {}

    /**
     * Every not-found branch below RETURNS a response instead of calling
     * abort(): see PublicationErrorBoundary's doc comment — a thrown
     * NotFoundHttpException is rendered by Illuminate\Routing\Pipeline via the
     * global ExceptionHandler at the exact pipe that threw it, and
     * bootstrap/app.php's own unconditional callback (admin::errors.index,
     * admin email) always wins that race. Returning a Response directly is
     * the only way this route group's 404s reach our own template.
     */
    public function redirect(Request $request, string $uuid): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        $publication = $this->resolveEnabledPublication($uuid, $type);

        if ($publication === null) {
            return $this->notFound();
        }

        $version = $this->resolver->resolveVersion($publication, null, $request->header('Accept-Language'));

        if ($version === null) {
            return $this->notFound();
        }

        return redirect()
            ->route('publication.public.'.$type.'.show.locale', ['uuid' => $uuid, 'locale' => $version->locale->code])
            ->header('Cache-Control', 'private, no-store')
            ->header('Vary', 'Accept-Language');
    }

    public function show(Request $request, string $uuid, string $locale): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        $definition = $this->registry->get($type);

        $publication = $this->resolveEnabledPublication($uuid, $type);

        if ($publication === null) {
            return $this->notFound();
        }

        $version = $this->resolver->resolveVersion($publication, $locale, null);

        if ($version === null) {
            return $this->notFound();
        }

        app()->setLocale($version->locale->code);

        $etag = '"'.hash_hmac('sha256', implode('|', [
            $version->checksum,
            $publication->status->value,
            $version->locale->code,
            self::TEMPLATE_VERSION,
        ]), config('app.key')).'"';

        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $view = view($definition->template, [
            'payload'   => $version->payload,
            'withdrawn' => $publication->status !== PublicationStatus::Published,
            'uuid'      => $publication->uuid,
            'locale'    => $version->locale->code,
            'locales'   => $publication->channel->locales,
        ]);

        return response($view->render())
            ->header('ETag', $etag)
            ->header('Cache-Control', 'public, max-age='.(int) (core()->getConfigData('general.publication.settings.cache_ttl', $publication->channel->code) ?? 3600))
            ->header('X-Robots-Tag', ((bool) (core()->getConfigData('general.publication.settings.indexable', $publication->channel->code) ?? false)) ? 'index, nofollow' : 'noindex, nofollow');
    }

    /**
     * Reads the `type` route default via the Request rather than accepting it
     * as a method parameter: Laravel's ControllerDispatcher binds non-class
     * method parameters POSITIONALLY (`array_values($route->parametersWithoutNulls())`),
     * never by name — confirmed by execution during development, where a
     * `string $type` parameter silently received the `{uuid}` segment's value
     * instead, because `defaults()` values are appended AFTER real URI
     * captures in that array, not in method-signature order. `uuid`/`locale`
     * stay as ordinary parameters above because they ARE real URI captures in
     * URI declaration order, which Laravel does guarantee; `type` never
     * appears in the URI at all, so it is not safe to bind positionally.
     */
    private function routeType(Request $request): string
    {
        return (string) $request->route('type');
    }

    /**
     * Resolves the publication and enforces every scope-derived gate against
     * values read from the resolved row itself — never from request input.
     * `general.publication.settings.enabled` (Task 7) is per-channel and is
     * the operational "unplug this channel's public tier" switch; it is
     * distinct from `catalog.product_passport.settings.enabled` (Task 8),
     * which only blocks *new* dpp publishes and is enforced solely in the
     * publish path (Tasks 12–13), never here.
     */
    private function resolveEnabledPublication(string $uuid, string $type): ?Publication
    {
        $publication = $this->resolver->findPublication($uuid, $type);

        if ($publication === null || ! $publication->status->isPubliclyResolvable()) {
            return null;
        }

        if (! (bool) (core()->getConfigData('general.publication.settings.enabled', $publication->channel->code) ?? true)) {
            return null;
        }

        return $publication;
    }

    private function notFound(): Response
    {
        return response()->view('publication::errors.404', [], 404);
    }
}
