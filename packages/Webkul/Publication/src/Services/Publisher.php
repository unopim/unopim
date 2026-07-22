<?php

namespace Webkul\Publication\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Product\Models\Product;
use Webkul\Publication\Contracts\PayloadBuilder;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Events\PublicationPublished;
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

        $payload = $builder->build($product, $channel, $locale);

        // meta.built_at is a rebuild timestamp, not content: hashing it would
        // change the checksum on every rebuild and defeat dedupe entirely.
        // Key order is also canonicalised before hashing: payloads assembled
        // from DB queries (Task 10) carry no guaranteed insertion order, so
        // two semantically identical payloads could otherwise hash
        // differently and mint a spurious version with no error.
        $canonical = $this->canonicalize(Arr::except($payload, 'meta.built_at'));
        $checksum = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($product, $channel, $locale, $type, $payload, $checksum, $publishedById): ?PublicationVersion {
            $publication = $this->publications->findOrCreateFor($product->id, $channel->id, $type);

            // Lock the publication row for the life of this transaction: two
            // queue workers publishing the same (publication, locale) pair
            // concurrently must not both read the same current version and
            // then race the (publication_id, locale_id, version) unique index.
            $publication = Publication::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            $current = $publication->currentVersion($locale->id);

            if ($current?->checksum === $checksum) {
                return null;
            }

            $current?->markSuperseded();

            $version = $publication->versions()->create([
                'locale_id'       => $locale->id,
                'version'         => ($current?->version ?? 0) + 1,
                'payload'         => $payload,
                'checksum'        => $checksum,
                'is_current'      => true,
                'published_at'    => now(),
                'published_by_id' => $publishedById,
            ]);

            // Withdrawal is a deliberate legal act: only a Draft is promoted
            // by a routine publish. A Withdrawn publication keeps minting
            // fresh content versions but must never be silently put back on
            // the air by a background job (see Task 8's auto_publish).
            if ($publication->status === PublicationStatus::Draft) {
                $publication->update(['status' => PublicationStatus::Published]);
            }

            PublicationPublished::dispatch($publication, $version);

            return $version;
        });
    }

    public function withdraw(Publication $publication): void
    {
        $publication->update(['status' => PublicationStatus::Withdrawn]);

        PublicationWithdrawn::dispatch($publication);
    }

    /**
     * Returns a withdrawn publication to Published. Throws when the
     * publication is not currently withdrawn, rather than silently no-op'ing,
     * so a caller (e.g. the future admin action in Tasks 12/13) cannot
     * mistake a bad request for a successful reinstatement.
     */
    public function reinstate(Publication $publication): void
    {
        if ($publication->status !== PublicationStatus::Withdrawn) {
            throw new InvalidPublicationTransitionException(
                'Publication '.$publication->id.' is not withdrawn; only a withdrawn publication can be reinstated.'
            );
        }

        $publication->update(['status' => PublicationStatus::Published]);

        PublicationReinstated::dispatch($publication);
    }

    /**
     * Recursively sorts associative-array keys so semantically identical
     * payloads hash identically regardless of DB row/key insertion order.
     * List arrays (sequential integer keys) are left untouched because their
     * order is meaningful data (e.g. `documents`, `sections`), not incidental
     * map ordering.
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

        if (! array_is_list($payload)) {
            ksort($payload);
        }

        return $payload;
    }
}
