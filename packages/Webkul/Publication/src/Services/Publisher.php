<?php

namespace Webkul\Publication\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\Contracts\PublicationGate;
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
    ) {}

    /**
     * Mints a new version when the payload checksum changed; returns null on gate failure or unchanged payload.
     */
    public function publish(
        Product $product,
        Channel $channel,
        Locale $locale,
        string $type,
        ?int $publishedById = null,
    ): ?PublicationVersion {
        $definition = $this->registry->get($type);

        $gate = $this->gateFor($definition);

        if ($gate !== null && ! $gate->passes($product, $channel, $locale)) {
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

        // Exclude meta (identity, not content) and canonicalize so dedupe isn't defeated by insertion order.
        $canonical = $this->canonicalize(Arr::except($payload, 'meta'));
        $checksum = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($publication, $locale, $payload, $checksum, $publishedById): ?PublicationVersion {
            // Lock the row so concurrent workers on the same (publication, locale) can't race the unique index.
            $publication = Publication::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            if (! $publication->status->acceptsNewVersions()) {
                throw new InvalidPublicationTransitionException(
                    'Publication '.$publication->id.' is '.$publication->status->value.'; reinstate it before publishing a locale.'
                );
            }

            $current = $publication->currentVersion($locale->id);

            if ($current?->checksum === $checksum) {
                return null;
            }

            // MAX(version), not $current->version+1: a sealed redacted version can be higher without being current.
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

            // Only Draft auto-promotes; Withdrawn/Redacted are sticky and need an explicit transition, not a publish.
            if ($publication->status === PublicationStatus::Draft) {
                $publication->update(['status' => PublicationStatus::Published]);
            }

            // Dispatch after commit so a slow listener doesn't extend lock hold and sees committed state.
            DB::afterCommit(fn () => PublicationPublished::dispatch($publication, $version));

            return $version;
        });
    }

    /**
     * Rollback by forward-only mint: because versions are immutable, "rolling
     * back" to an older version copies its frozen payload into a NEW current
     * version with a fresh version number, leaving the source row untouched.
     *
     * Returns null when the source is already the live version (nothing to do).
     * Refuses on a redacted publication or a redacted source — the payload bytes
     * are gone, so there is nothing to republish.
     */
    public function republishFrom(PublicationVersion $source, ?int $publishedById = null): ?PublicationVersion
    {
        return DB::transaction(function () use ($source, $publishedById): ?PublicationVersion {
            // Lock the row so a concurrent publish/republish on the same (publication, locale) can't race the unique index.
            $publication = Publication::query()->whereKey($source->publication_id)->lockForUpdate()->firstOrFail();

            if (! $publication->status->acceptsNewVersions()) {
                throw new InvalidPublicationTransitionException(
                    'Publication '.$publication->id.' is '.$publication->status->value.'; reinstate it before republishing a version.'
                );
            }

            $payload = $source->payload;

            if ($source->redacted_at !== null || $payload === null) {
                throw new InvalidPublicationTransitionException(
                    'Version '.$source->id.' has no payload to republish.'
                );
            }

            $localeId = (int) $source->locale_id;

            $current = $publication->currentVersion($localeId);

            if ($current !== null && $current->getKey() === $source->getKey()) {
                return null;
            }

            // MAX(version)+1, mirroring publish(): a sealed redacted version can be higher without being current.
            $nextVersion = $publication->versions()
                ->where('locale_id', $localeId)
                ->max('version') + 1;

            $current?->markSuperseded();

            $version = $publication->versions()->create([
                'locale_id'       => $localeId,
                'version'         => $nextVersion,
                'payload'         => $payload,
                'checksum'        => $source->checksum,
                'is_current'      => true,
                'published_at'    => now(),
                'published_by_id' => $publishedById,
            ]);

            // Only Draft auto-promotes; Withdrawn/Redacted are sticky and need an explicit transition, not a republish.
            if ($publication->status === PublicationStatus::Draft) {
                $publication->update(['status' => PublicationStatus::Published]);
            }

            DB::afterCommit(fn () => PublicationPublished::dispatch($publication, $version));

            return $version;
        });
    }

    /**
     * Marks the publication Withdrawn. Reversible via reinstate(); throws when the
     * publication is not published, so a redacted one can never be walked back to
     * withdrawn and a no-op withdrawal cannot pass for a real state change.
     */
    public function withdraw(Publication $publication): void
    {
        if ($publication->status !== PublicationStatus::Published) {
            throw new InvalidPublicationTransitionException(
                'Publication '.$publication->id.' is not published; only a published publication can be withdrawn.'
            );
        }

        $publication->update(['status' => PublicationStatus::Withdrawn]);

        DB::afterCommit(fn () => PublicationWithdrawn::dispatch($publication));
    }

    /**
     * Returns a withdrawn publication to Published; throws when not withdrawn, so redaction stays one-way.
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
     * GDPR Art. 17 erasure: redacts every not-yet-redacted version of the publication, superseded ones
     * included, and flips the publication to Redacted (sticky).
     *
     * A superseded version is still a sealed record of the same data. Redacting only the current ones
     * left every earlier payload readable in the database, so the erasure held only for as long as
     * nothing ever read history. Versions redacted individually earlier are left as they are.
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

            $versions = $publication->versions()->whereNull('redacted_at')->get();

            if ($versions->isEmpty()) {
                throw new InvalidPublicationTransitionException(
                    'Publication '.$publication->id.' has no versions left to redact.'
                );
            }

            foreach ($versions as $version) {
                $version->redact($redactedById, $reason);
            }

            $publication->update(['status' => PublicationStatus::Redacted]);

            DB::afterCommit(fn () => PublicationRedacted::dispatch($publication, $reason));
        });
    }

    /**
     * A type without a gate publishes unconditionally; the engine itself has no
     * notion of what makes a publication complete.
     */
    private function gateFor(PublicationType $definition): ?PublicationGate
    {
        if ($definition->gate === null) {
            return null;
        }

        $gate = resolve($definition->gate);

        if (! $gate instanceof PublicationGate) {
            throw new InvalidArgumentException(
                'Publication type ['.$definition->code.'] declares gate ['.$definition->gate.'], which does not implement '.PublicationGate::class.'.'
            );
        }

        return $gate;
    }

    /**
     * Canonical `/{routePrefix}/{uuid}/{locale}` URL for the public tier.
     */
    private function canonicalUrl(PublicationType $definition, string $uuid, Channel $channel, Locale $locale): string
    {
        $base = core()->getConfigData('general.publication.settings.base_url', $channel->code) ?: config('app.url');

        return rtrim((string) $base, '/')."/{$definition->routePrefix}/{$uuid}/{$locale->code}";
    }

    /**
     * Recursively sorts keys/list items so identical payloads hash identically regardless of row order (hashing only).
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
     * Sorts by each item's `code`, falling back to canonical JSON so the sort stays stable for any shape.
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
