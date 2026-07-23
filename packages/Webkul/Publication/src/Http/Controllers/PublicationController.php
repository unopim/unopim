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
     * Every not-found branch below returns a Response instead of calling
     * abort(): a thrown exception is rendered by Laravel's routing Pipeline
     * via the global handler at the pipe that threw it, bypassing this
     * package's own template (see PublicationErrorBoundary).
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

    /**
     * Renders the publication, honouring If-None-Match against a checksum-derived ETag.
     */
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
     * Reads the `type` route default via the Request rather than as a method
     * parameter: ControllerDispatcher binds non-class parameters positionally,
     * and `defaults()` values are appended after real URI captures — a
     * `string $type` parameter would silently receive `{uuid}`'s value instead.
     */
    private function routeType(Request $request): string
    {
        return (string) $request->route('type');
    }

    /**
     * Resolves the publication and enforces scope gates against the resolved
     * row itself, never request input. `general.publication.settings.enabled`
     * is the per-channel public-tier kill switch; it's distinct from the
     * publish-time-only gate enforced elsewhere, never here.
     */
    private function resolveEnabledPublication(string $uuid, string $type): ?Publication
    {
        $publication = $this->resolver->findPublication($uuid, $type);

        if ($publication === null || ! $publication->status->isPubliclyResolvable()) {
            return null;
        }

        if (! $this->resolver->isChannelEnabled($publication)) {
            return null;
        }

        return $publication;
    }

    private function notFound(): Response
    {
        return response()->view('publication::errors.404', [], 404);
    }
}
