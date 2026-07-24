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
     * GS1 Digital Link entry point: maps a scanned `/01/{gtin}` to the product's
     * designated passport and 302s to its canonical per-locale URL, where the
     * existing Accept-Language resolution picks the language. Honours the same
     * publicly-resolvable-status and channel-enabled gates as every other public
     * route, keyed by the resolved row rather than request input.
     */
    public function resolveByGtin(Request $request, string $gtin): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        $publication = $this->resolver->findByGtin($gtin, $type);

        if (
            $publication === null
            || ! $publication->status->isPubliclyResolvable()
            || ! $this->resolver->isChannelEnabled($publication)
        ) {
            return $this->notFound();
        }

        $version = $this->resolver->resolveVersion($publication, null, $request->header('Accept-Language'));

        if ($version === null) {
            return $this->notFound();
        }

        return redirect()
            ->route('publication.public.'.$type.'.show.locale', ['uuid' => $publication->uuid, 'locale' => $version->locale->code])
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

        [$granted, $grantedIndex] = $this->grantedTier($request);
        $payload = $this->applyTierGate($version->payload, $grantedIndex);

        // Only a Published passport exposes payload content: withdrawn/redacted
        // states are publicly resolvable but the HTML path renders a tombstone
        // only, so the JSON-LD branch must fall through to that same tombstone
        // rather than leak the frozen payload as machine-readable content.
        if (
            $definition->jsonld !== null
            && $publication->status === PublicationStatus::Published
            && str_contains((string) $request->header('Accept'), 'application/ld+json')
        ) {
            app()->setLocale($version->locale->code);

            $jsonldClass = $definition->jsonld;

            return $this->tierCache(
                response()
                    ->json((new $jsonldClass($payload))->toArray($request))
                    ->header('Content-Type', 'application/ld+json')
                    ->header('X-Robots-Tag', 'noindex, nofollow'),
                $grantedIndex,
            );
        }

        app()->setLocale($version->locale->code);

        $etag = '"'.hash_hmac('sha256', implode('|', [
            $version->checksum,
            $publication->status->value,
            $version->locale->code,
            $granted,
            self::TEMPLATE_VERSION,
        ]), config('app.key')).'"';

        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        $view = view($definition->template, [
            'payload'   => $payload,
            'withdrawn' => $publication->status !== PublicationStatus::Published,
            'uuid'      => $publication->uuid,
            'locale'    => $version->locale->code,
            'locales'   => $publication->channel->locales,
        ]);

        return $this->tierCache(
            response($view->render())
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age='.(int) (core()->getConfigData('general.publication.settings.cache_ttl', $publication->channel->code) ?? 3600))
                ->header('X-Robots-Tag', ((bool) (core()->getConfigData('general.publication.settings.indexable', $publication->channel->code) ?? false)) ? 'index, nofollow' : 'noindex, nofollow'),
            $grantedIndex,
        );
    }

    /**
     * Resolves the ESPR access tier this request is authorised for. Elevation
     * above `consumer` is granted ONLY by a valid Laravel signed URL carrying a
     * `tier` param that exists in the configured order — any missing/invalid
     * signature, unknown tier, or tier outside `order` fails closed to the base
     * tier. The `tier` param is never trusted without a valid signature, so an
     * unsigned `?tier=authority` can never widen the surface.
     *
     * @return array{0: string, 1: int}
     */
    private function grantedTier(Request $request): array
    {
        $order = config('publication.tiers.order', ['consumer']);
        $base = $order[0] ?? 'consumer';
        $requested = (string) $request->query('tier', $base);

        $granted = ($request->hasValidSignature() && in_array($requested, $order, true)) ? $requested : $base;

        return [$granted, (int) array_search($granted, $order, true)];
    }

    /**
     * Collapses the tier-partitioned payload down to only the fields/documents
     * visible up to the granted tier, overwriting the base `sections`/`documents`
     * the template and JSON-LD resource read. A payload without a `tiers` key
     * (a frozen version built before tiering, or a redacted null payload) is
     * returned untouched — its existing base shape is already the consumer view.
     */
    private function applyTierGate(mixed $payload, int $grantedIndex): mixed
    {
        if (! is_array($payload) || ! isset($payload['tiers']) || ! is_array($payload['tiers'])) {
            return $payload;
        }

        $order = array_keys($payload['tiers']);
        $fields = [];
        $documents = [];

        foreach (array_slice($order, 0, $grantedIndex + 1) as $tier) {
            $fields = array_merge($fields, $payload['tiers'][$tier]['fields'] ?? []);
            $documents = array_merge($documents, $payload['tiers'][$tier]['documents'] ?? []);
        }

        $payload['sections'][0]['fields'] = $fields;
        $payload['documents'] = $documents;
        unset($payload['tiers']);

        return $payload;
    }

    /**
     * An elevated (above-base) tier response carries content a signed URL
     * holder is uniquely authorised to see, so it must never enter a shared
     * cache; the base tier keeps whatever caching the caller already set.
     */
    private function tierCache(Response $response, int $grantedIndex): Response
    {
        if ($grantedIndex > 0) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
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
