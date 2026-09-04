<?php

namespace Webkul\Publication\Services;

use InvalidArgumentException;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationCarrierIssuance;
use Webkul\Publication\Models\PublicationRelease;
use Webkul\Publication\Registry\PublicationTypeRegistry;

/**
 * Issues a printable carrier bound to one release and records exactly what it encodes.
 */
class CarrierIssuer
{
    public function __construct(private readonly PublicationTypeRegistry $registry) {}

    /**
     * The URL a release carrier encodes: `<base>/{prefix}/{uuid}/r/{sequence}`. Locale is
     * negotiated once at scan time by the entry route, the same way the live carrier works.
     * The base is the per-channel passport base URL, like every other printed link.
     */
    public function targetFor(Publication $publication, PublicationRelease $release): string
    {
        $definition = $this->registry->get($publication->type);

        $base = core()->getConfigData('general.publication.settings.base_url', $publication->channel?->code)
            ?: config('app.url');

        $path = route('publication.public.'.$definition->code.'.show.release.entry', [
            'uuid'     => $publication->uuid,
            'sequence' => $release->sequence,
        ], false);

        return rtrim((string) $base, '/').$path;
    }

    public function issue(Publication $publication, PublicationRelease $release, ?int $issuedById = null): PublicationCarrierIssuance
    {
        if ((int) $release->publication_id !== (int) $publication->id) {
            throw new InvalidArgumentException('Release '.$release->id.' does not belong to publication '.$publication->id.'.');
        }

        return $publication->carrierIssuances()->create([
            'release_id'   => $release->id,
            'target'       => $this->targetFor($publication, $release),
            'format'       => 'svg',
            'issued_at'    => now(),
            'issued_by_id' => $issuedById,
        ]);
    }
}
