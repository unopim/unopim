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

    /**
     * Builds the payload, hashes it, and mints a new version when the
     * checksum changed. Returns null when the completeness gate fails or
     * the payload is unchanged since the current version.
     */
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

        // Publication must exist before build(): the payload stamps its own uuid/url from it.
        $publication = $this->publications->findOrCreateFor($product->id, $channel->id, $type);

        $context = new PublicationContext(
            uuid: $publication->uuid,
            channel: $channel,
            locale: $locale,
            url: $this->canonicalUrl($definition, $publication->uuid, $channel, $locale),
        );

        $payload = $builder->build($product, $context);

        // meta is identity/rebuild metadata, not content - hashing it would defeat
        // dedupe. Keys are also canonicalized so insertion order can't change the hash.
        $canonical = $this->canonicalize(Arr::except($payload, 'meta'));
        $checksum = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publication, $locale, $payload, $checksum, $publishedById): ?PublicationVersion {
            // Lock the row: concurrent workers publishing the same (publication, locale)
            // must not both read the same current version and race the unique index.
            $publication = Publication::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            $current = $publication->currentVersion($locale->id);

            if ($current?->checksum === $checksum) {
                return null;
            }

            // MAX(version), not $current->version+1: redaction can leave a higher
            // version sealed without flipping is_current, so $current isn't authoritative.
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

            // Only a Draft is auto-promoted; Withdrawn/Redacted stay sticky and require
            // an explicit reinstate()/redactAll() call, never a routine publish.
            if ($publication->status === PublicationStatus::Draft) {
                $publication->update(['status' => PublicationStatus::Published]);
            }

            // Dispatch after commit: a slow listener must not extend lock hold time,
            // and it must see committed state, not a lock it can't read past.
            DB::afterCommit(fn () => PublicationPublished::dispatch($publication, $version));

            return $version;
        });
    }

    /**
     * Marks the publication Withdrawn. Reversible via reinstate().
     */
    public function withdraw(Publication $publication): void
    {
        $publication->update(['status' => PublicationStatus::Withdrawn]);

        DB::afterCommit(fn () => PublicationWithdrawn::dispatch($publication));
    }

    /**
     * Returns a withdrawn publication to Published. Throws instead of no-op
     * when not withdrawn, so a Redacted publication can never be reinstated
     * this way — redaction is one-way by design.
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
     * GDPR Art. 17 erasure: redacts every current version and flips the
     * publication to Redacted (sticky, like Withdrawn). Throws rather than
     * no-op'ing when already redacted or with nothing to redact.
     */
    public function redactAll(Publication $publication, int $redactedById, string $reason): void
    {
        DB::transaction(function () use ($publication, $redactedById, $reason): void {
            // Re-fetch under lock so two concurrent calls can't both pass the check below.
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
     * Best-effort canonical URL for the not-yet-shipped public tier, matching
     * the `/{routePrefix}/{uuid}/{locale}` shape those routes will register.
     */
    private function canonicalUrl(PublicationType $definition, string $uuid, Channel $channel, Locale $locale): string
    {
        $base = core()->getConfigData('general.publication.settings.base_url', $channel->code) ?: config('app.url');

        return rtrim((string) $base, '/')."/{$definition->routePrefix}/{$uuid}/{$locale->code}";
    }

    /**
     * Recursively sorts keys/list items so identical payloads hash
     * identically regardless of DB row order. For hashing only — the stored
     * `$payload` keeps its original, meaningful order.
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
     * Sorts by each item's `code`, falling back to canonical JSON encoding
     * for shapes without one, so the sort stays stable for any content shape.
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
