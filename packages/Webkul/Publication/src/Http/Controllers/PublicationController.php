<?php

namespace Webkul\Publication\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Jobs\RecordPublicationView;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;
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
     * Not-found branches return a Response, never abort(): a thrown exception bypasses this package's own 404 template.
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
     * GS1 Digital Link entry point: maps a scanned `/01/{gtin}` to its passport and 302s to the canonical per-locale URL.
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
     *
     * The locale switcher is built from the published versions, not the channel's
     * locales: a channel locale with no current version has no URL to switch to.
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

        // Only Published exposes payload: withdrawn/redacted must fall through to the tombstone, not leak JSON-LD.
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

        // Count only a live (Published) full HTML render — never a 304, a JSON-LD
        // negotiation, or a withdrawn/redacted tombstone. Queued so the render is
        // not slowed, and it stores a daily count only: no IP, no visitor identity.
        if ($publication->status === PublicationStatus::Published) {
            RecordPublicationView::dispatch($publication->id, (int) $version->locale->id);
        }

        $view = view($definition->template, [
            'payload'   => $payload,
            'withdrawn' => $publication->status !== PublicationStatus::Published,
            'uuid'      => $publication->uuid,
            'locale'    => $version->locale->code,
            'locales'   => $publication->versions
                ->map(fn (PublicationVersion $published) => $published->locale)
                ->sortBy('code')
                ->values(),
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
     * Resolves the granted ESPR tier. Elevation above base requires a valid signed URL, else it fails closed to base.
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
     * Collapses the tier-partitioned payload to the fields/documents visible up to the granted tier.
     * A payload without a `tiers` key (pre-tiering or redacted) is returned untouched.
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
     * Elevated-tier responses carry signed-URL-holder content, so they must never enter a shared cache.
     */
    private function tierCache(Response $response, int $grantedIndex): Response
    {
        if ($grantedIndex > 0) {
            $response->headers->set('Cache-Control', 'private, no-store');
        }

        return $response;
    }

    /**
     * Reads `type` via the Request, not a parameter: route defaults are appended after URI captures, so a
     * `string $type` parameter would receive `{uuid}`'s value.
     */
    private function routeType(Request $request): string
    {
        return (string) $request->route('type');
    }

    /**
     * Resolves the publication and enforces the resolvable-status and per-channel-enabled gates against the row itself.
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
