<?php

namespace Webkul\Publication\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\DataTransferObjects\PublicationContext;
use Webkul\Publication\DataTransferObjects\PublicationType;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Events\PublicationRedacted;
use Webkul\Publication\Events\PublicationReinstated;
use Webkul\Publication\Events\PublicationWithdrawn;
use Webkul\Publication\Exceptions\InvalidPublicationTransitionException;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationVersion;
use Webkul\Publication\Registry\PublicationTypeRegistry;
use Webkul\Publication\Repositories\PublicationRepository;

class Publisher
{
    public function __construct(
        private readonly PublicationTypeRegistry $registry,
        private readonly PublicationRepository $publications,
        private readonly CompletenessGate $gate,
    ) {}

    public function publish(
        Product $product,
        Channel $channel,
        Locale $locale,
        string $type,
        ?int $publishedById = null,
    ): ?PublicationVersion {
        $definition = $this->registry->get($type);

        if (! $this->gate->passes($product, $channel, $locale)) {
            return null;
        }

        $builder = resolve($definition->payloadBuilder);

        if (! $builder instanceof PayloadBuilder) {
            throw new InvalidArgumentException(
                'Publication type ['.$type.'] declares payload builder ['.$definition->payloadBuilder.'], which does not implement '.PayloadBuilder::class.'.'
            );
        }

        // findOrCreateFor() runs before build(): the payload must be able to
        // stamp its own uuid and canonical URL (PublicationContext) as it is
        // built, and it cannot do that for a publication that does not exist
        // yet.
        $publication = $this->publications->findOrCreateFor($product->id, $channel->id, $type);

        $context = new PublicationContext(
            uuid: $publication->uuid,
            channel: $channel,
            locale: $locale,
            url: $this->canonicalUrl($definition, $publication->uuid, $channel, $locale),
        );

        $payload = $builder->build($product, $context);

        // meta is identity/rebuild metadata, not content: hashing it would
        // change the checksum on every rebuild (or on first stamp of the
        // uuid/url) and defeat dedupe entirely. This is a whitelist, not a
        // blacklist of individual known-volatile keys, so a future key added
        // under meta can never silently poison the checksum. Key order is
        // also canonicalised before hashing: payloads assembled from DB
        // queries (Task 10) carry no guaranteed insertion order, so two
        // semantically identical payloads could otherwise hash differently
        // and mint a spurious version with no error.
        $canonical = $this->canonicalize(Arr::except($payload, 'meta'));
        $checksum = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publication, $locale, $payload, $checksum, $publishedById): ?PublicationVersion {
            // Lock the publication row for the life of this transaction: two
            // queue workers publishing the same (publication, locale) pair
            // concurrently must not both read the same current version and
            // then race the (publication_id, locale_id, version) unique index.
            $publication = Publication::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            $current = $publication->currentVersion($locale->id);

            if ($current?->checksum === $checksum) {
                return null;
            }

            // MAX(version), not $current->version: $current is the CURRENT
            // version, not necessarily the highest ever minted for this
            // locale. A redaction (or any future supersede-without-republish
            // path) can leave a higher version sealed in history without
            // ever flipping is_current back, so trusting $current alone
            // could re-mint an already-used version number.
            $nextVersion = $publication->versions()
                ->where('locale_id', $locale->id)
                ->max('version') + 1;

            $current?->markSuperseded();

            $version = $publication->versions()->create([
                'locale_id'       => $locale->id,
                'version'         => $nextVersion,
                'payload'         => $payload,
                'checksum'        => $checksum,
                'is_current'      => true,
                'published_at'    => now(),
                'published_by_id' => $publishedById,
            ]);

            // Withdrawal and redaction are both deliberate legal acts, and
            // both sticky exactly the same way: only a Draft is promoted by
            // a routine publish. A Withdrawn or Redacted publication keeps
            // minting fresh content versions but must never be silently put
            // back on the air by a background job (see Task 8's
            // auto_publish) — republishing corrected content after a
            // redaction is legitimate, but only an explicit reinstate() (for
            // Withdrawn) may resurrect the publication's own status.
            if ($publication->status === PublicationStatus::Draft) {
                $publication->update(['status' => PublicationStatus::Published]);
            }

            // Dispatched after the transaction commits, not while still
            // holding lockForUpdate(): a slow listener must not extend lock
            // hold time and block every other worker publishing the same
            // product, and a listener that queries this row must see
            // committed state, not a lock it cannot even read past.
            DB::afterCommit(fn () => PublicationPublished::dispatch($publication, $version));

            return $version;
        });
    }

    public function withdraw(Publication $publication): void
    {
        $publication->update(['status' => PublicationStatus::Withdrawn]);

        DB::afterCommit(fn () => PublicationWithdrawn::dispatch($publication));
    }

    /**
     * Returns a withdrawn publication to Published. Throws when the
     * publication is not currently withdrawn, rather than silently no-op'ing,
     * so a caller (e.g. the future admin action in Tasks 12/13) cannot
     * mistake a bad request for a successful reinstatement. This also means a
     * Redacted publication can never be reinstated through this method
     * (Redacted !== Withdrawn) — redaction is one-way by design.
     */
    public function reinstate(Publication $publication): void
    {
        if ($publication->status !== PublicationStatus::Withdrawn) {
            throw new InvalidPublicationTransitionException(
                'Publication '.$publication->id.' is not withdrawn; only a withdrawn publication can be reinstated.'
            );
        }

        $publication->update(['status' => PublicationStatus::Published]);

        DB::afterCommit(fn () => PublicationReinstated::dispatch($publication));
    }

    /**
     * GDPR Art. 17 erasure at the publication level: redacts every current
     * (one per locale) version and flips the publication's own status to
     * Redacted — sticky exactly like Withdrawn, see publish()'s Draft-only
     * promotion check. Throws rather than silently no-op'ing on either an
     * already-redacted publication or one with nothing attested to redact,
     * for the same reason reinstate() throws on a non-withdrawn publication.
     */
    public function redactAll(Publication $publication, int $redactedById, string $reason): void
    {
        DB::transaction(function () use ($publication, $redactedById, $reason): void {
            // Re-fetch under lock (not the caller's possibly-stale instance)
            // so two concurrent redactAll() calls on the same publication
            // cannot both pass the "already redacted" check below.
            $publication = Publication::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            if ($publication->status === PublicationStatus::Redacted) {
                throw new InvalidPublicationTransitionException(
                    'Publication '.$publication->id.' is already redacted.'
                );
            }

            $currentVersions = $publication->versions()->where('is_current', true)->get();

            if ($currentVersions->isEmpty()) {
                throw new InvalidPublicationTransitionException(
                    'Publication '.$publication->id.' has no current versions to redact.'
                );
            }

            foreach ($currentVersions as $version) {
                $version->redact($redactedById, $reason);
            }

            $publication->update(['status' => PublicationStatus::Redacted]);

            DB::afterCommit(fn () => PublicationRedacted::dispatch($publication, $reason));
        });
    }

    /**
     * Best-effort canonical URL for the yet-to-ship public tier (Task 5),
     * mirroring the route shape already documented for it
     * (`/{routePrefix}/{uuid}/{locale}`). Not built via route() because
     * those routes do not exist yet; Task 5 registers them under this same
     * prefix, so this stays accurate once it does.
     */
    private function canonicalUrl(PublicationType $definition, string $uuid, Channel $channel, Locale $locale): string
    {
        $base = core()->getConfigData('general.publication.settings.base_url', $channel->code) ?: config('app.url');

        return rtrim((string) $base, '/')."/{$definition->routePrefix}/{$uuid}/{$locale->code}";
    }

    /**
     * Recursively sorts associative-array keys, and sorts list arrays by each
     * item's stable business key, so semantically identical payloads hash
     * identically regardless of DB row/key insertion order. This is for
     * hashing only: the return value here feeds the checksum, never the
     * `$payload` array that actually gets stored — a list's stored order
     * (e.g. `documents`, `sections`) is meaningful display data, not
     * incidental map ordering, and must never be reordered on disk.
     *
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function canonicalize(array $payload): array
    {
        $payload = array_map(
            fn (mixed $value): mixed => is_array($value) ? $this->canonicalize($value) : $value,
            $payload,
        );

        if (array_is_list($payload)) {
            return $this->sortListForHashing($payload);
        }

        ksort($payload);

        return $payload;
    }

    /**
     * Sorts a content list by each item's `code` where present, falling back
     * to the item's own canonical JSON encoding for shapes with no `code`
     * (e.g. lists of scalars, or heterogeneous items), so the sort is always
     * stable regardless of the list's real content shape.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function sortListForHashing(array $items): array
    {
        $sortKey = fn (mixed $item): string => is_array($item) && array_key_exists('code', $item)
            ? (string) $item['code']
            : json_encode($item, JSON_THROW_ON_ERROR);

        usort($items, fn (mixed $a, mixed $b): int => $sortKey($a) <=> $sortKey($b));

        return $items;
    }
}
