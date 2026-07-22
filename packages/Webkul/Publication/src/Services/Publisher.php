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
use Webkul\Publication\Events\PublicationWithdrawn;
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
        $checksum = hash('sha256', json_encode(Arr::except($payload, 'meta.built_at'), JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($product, $channel, $locale, $type, $payload, $checksum, $publishedById): ?PublicationVersion {
            $publication = $this->publications->findOrCreateFor($product->id, $channel->id, $type);

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

            if ($publication->status !== PublicationStatus::Published) {
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
}
