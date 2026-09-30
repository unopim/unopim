<?php

namespace Webkul\Publication\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Publication\Contracts\LotReleaseResolver;
use Webkul\Publication\DataTransferObjects\PublicationType;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Jobs\RecordPublicationView;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationRelease;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Registry\PublicationTypeRegistry;
use Webkul\Publication\Services\Gs1DigitalLink;
use Webkul\Publication\Services\PublicationResolver;

class PublicationController extends Controller
{
    /**
     * Bump when the rendered template's HTML shape changes in a way that must
     * invalidate every previously cached ETag, independent of payload content.
     */
    private const TEMPLATE_VERSION = '2';

    public function __construct(
        private readonly PublicationResolver $resolver,
        private readonly PublicationTypeRegistry $registry,
        private readonly Gs1DigitalLink $gs1,
        private readonly LotReleaseResolver $lots,
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
        return $this->resolveByGtinQualified($request, $gtin, null, null);
    }

    /**
     * GS1 Digital Link with qualifiers: `/01/{gtin}/10/{lot}`, `/01/{gtin}/21/{serial}` or both. The lot or serial
     * ties a scanned unit to the release it was placed on the market under. Which release that is lives outside the
     * PIM, so it is asked from the bound LotReleaseResolver; when it does not know, the scan lands on the live
     * passport exactly as an unqualified link does. A malformed qualifier is a bad link, not a live fallback: 404.
     */
    public function resolveByGtinQualified(Request $request, string $gtin, ?string $lot = null, ?string $serial = null): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        if (! $this->gs1->isWellFormedQualifier($lot) || ! $this->gs1->isWellFormedQualifier($serial)) {
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

        $release = ($lot === null && $serial === null) ? null : $this->lots->resolve($publication, $lot, $serial);

        if ($release !== null && (int) $release->publication_id === (int) $publication->id) {
            $version = $this->resolver->pickVersion($publication, $release->versionsAsOf(), null, $request->header('Accept-Language'));

            if ($version === null) {
                return $this->notFound();
            }

            return redirect()
                ->route('publication.public.'.$type.'.show.release', [
                    'uuid'     => $publication->uuid,
                    'sequence' => $release->sequence,
                    'locale'   => $version->locale->code,
                ])
                ->header('Cache-Control', 'private, no-store')
                ->header('Vary', 'Accept-Language');
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
     * Renders the live state: the current version for the requested locale, honouring If-None-Match.
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

        $publication = $this->resolveEnabledPublication($uuid, $type);

        if ($publication === null) {
            return $this->notFound();
        }

        $version = $this->resolver->resolveVersion($publication, $locale, null);

        if ($version === null) {
            return $this->notFound();
        }

        return $this->render($request, $this->registry->get($type), $publication, $version, null, $publication->versions);
    }

    /**
     * Entry point for a printed release carrier: picks the locale from Accept-Language among the locales
     * that exist in that release and 302s to the strict per-locale URL, mirroring the live `redirect()`.
     */
    public function redirectRelease(Request $request, string $uuid, string $sequence): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        $publication = $this->resolveEnabledPublication($uuid, $type);

        if ($publication === null) {
            return $this->notFound();
        }

        $release = $this->resolver->findRelease($publication, (int) $sequence);

        if ($release === null) {
            return $this->notFound();
        }

        $version = $this->resolver->pickVersion($publication, $release->versionsAsOf(), null, $request->header('Accept-Language'));

        if ($version === null) {
            return $this->notFound();
        }

        return redirect()
            ->route('publication.public.'.$type.'.show.release', [
                'uuid'     => $uuid,
                'sequence' => $release->sequence,
                'locale'   => $version->locale->code,
            ])
            ->header('Cache-Control', 'private, no-store')
            ->header('Vary', 'Accept-Language');
    }

    /**
     * Renders the state as of one release: for the requested locale, the most recent version minted at
     * or before that release. Strict locale, no Accept-Language negotiation: an explicit historical URL
     * is a reference, and a reference must not resolve to a different document per reader.
     */
    public function showRelease(Request $request, string $uuid, string $sequence, string $locale): Response
    {
        $type = $this->routeType($request);

        if (! $this->registry->has($type)) {
            return $this->notFound();
        }

        $publication = $this->resolveEnabledPublication($uuid, $type);

        if ($publication === null) {
            return $this->notFound();
        }

        $release = $this->resolver->findRelease($publication, (int) $sequence);

        if ($release === null) {
            return $this->notFound();
        }

        $versions = $release->versionsAsOf();

        $version = $versions->first(fn (PublicationVersion $candidate): bool => $candidate->locale->code === $locale);

        if ($version === null) {
            return $this->notFound();
        }

        return $this->render($request, $this->registry->get($type), $publication, $version, $release, $versions);
    }

    /**
     * Shared by the live and the release routes. A release page differs in three ways: its locale switcher
     * stays inside the release, it is never indexed and always carries a canonical link to the live page,
     * and it never negotiates JSON-LD (a historical payload's stamped identity is the publication URL,
     * which would misdescribe it).
     *
     * @param  Collection<int, PublicationVersion>  $switchable  the versions the locale switcher may link to
     */
    private function render(
        Request $request,
        PublicationType $definition,
        Publication $publication,
        PublicationVersion $version,
        ?PublicationRelease $release,
        Collection $switchable,
    ): Response {
        [$granted, $grantedIndex] = $this->grantedTier($request);
        $payload = $this->applyTierGate($version->payload, $grantedIndex);

        // An individually redacted version is a tombstone even under a Published publication: its payload is gone.
        $gone = $publication->status === PublicationStatus::Redacted || $version->redacted_at !== null;
        $tombstone = $gone || $publication->status !== PublicationStatus::Published;

        // Only the live Published state exposes payload as JSON-LD: tombstones must not leak it, historical states are HTML only.
        if (
            $release === null
            && ! $tombstone
            && $definition->jsonld !== null
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

        // Release pages carry a banner whose text depends on whether the state is still current, and a
        // redacted version renders as a tombstone; both change the HTML without changing the checksum.
        $etag = '"'.hash_hmac('sha256', implode('|', [
            $version->checksum,
            $publication->status->value,
            $version->locale->code,
            $granted,
            self::TEMPLATE_VERSION,
            $release?->sequence ?? 'live',
            $version->is_current ? 'current' : 'superseded',
            $version->redacted_at === null ? 'intact' : 'redacted',
        ]), config('app.key')).'"';

        if (trim((string) $request->header('If-None-Match')) === $etag) {
            return response('', 304)->header('ETag', $etag);
        }

        // Count only a live (Published) full HTML render — never a 304, a JSON-LD
        // negotiation, a tombstone, or a historical release page. Queued so the render is
        // not slowed, and it stores a daily count only: no IP, no visitor identity.
        if ($release === null && ! $tombstone) {
            RecordPublicationView::dispatch($publication->id, (int) $version->locale->id);
        }

        $liveUrl = route('publication.public.'.$definition->code.'.show.locale', [
            'uuid'   => $publication->uuid,
            'locale' => $version->locale->code,
        ]);

        $view = view($definition->template, [
            'payload'   => $payload,
            'withdrawn' => $tombstone,
            'uuid'      => $publication->uuid,
            'locale'    => $version->locale->code,
            'locales'   => $switchable
                ->map(fn (PublicationVersion $published) => $published->locale)
                ->sortBy('code')
                ->values(),
            'release'   => $release === null ? null : [
                'sequence'     => (int) $release->sequence,
                'is_current'   => (bool) $version->is_current,
                'published_at' => $release->published_at,
                'current_url'  => $liveUrl,
            ],
        ]);

        $robots = match (true) {
            $release !== null => 'noindex, noarchive, nofollow',
            $tombstone        => 'noindex, nofollow',
            default           => ((bool) (core()->getConfigData('general.publication.settings.indexable', $publication->channel->code) ?? false)) ? 'index, nofollow' : 'noindex, nofollow',
        };

        // Redacted is irreversible, so it is 410 Gone; Withdrawn can be reinstated, so it stays 200.
        // No tombstone may sit in a shared cache: there is no purge hook to displace it on reinstatement.
        $response = response($view->render(), $gone ? 410 : 200)
            ->header('ETag', $etag)
            ->header('Cache-Control', $tombstone ? 'private, no-store' : $this->cacheControl($publication->channel?->code))
            ->header('X-Robots-Tag', $robots);

        if ($release !== null) {
            $response->header('Link', '<'.$liveUrl.'>; rel="canonical"');
        }

        return $this->tierCache($response, $grantedIndex);
    }

    /**
     * A passport is a legal artifact that has to stop being served the moment it
     * is withdrawn or the channel's public tier is switched off, so browsers
     * revalidate every visit — the ETag answers 304 and the render is skipped.
     * Shared caches still hold the page for the configured TTL, where a purge is
     * available; `max-age` alone left an already-issued page live for an hour
     * after the kill switch, which is what made a disabled tier look ignored.
     */
    private function cacheControl(?string $channelCode): string
    {
        $ttl = (int) (core()->getConfigData('general.publication.settings.cache_ttl', $channelCode) ?? 300);

        return 'public, max-age=0, s-maxage='.max(0, $ttl).', must-revalidate';
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

    /**
     * Never cached: a 404 here is a switch state, not a fact about the URL, and a
     * cached one would outlive re-enabling the tier.
     */
    private function notFound(): Response
    {
        return response()
            ->view('publication::errors.404', [], 404)
            ->header('Cache-Control', 'no-store');
    }
}
