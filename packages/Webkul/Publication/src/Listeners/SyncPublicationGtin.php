<?php

namespace Webkul\Publication\Listeners;

use Webkul\Publication\Events\PublicationPublished;
use Webkul\Publication\Models\PublicationProxy;
use Webkul\Publication\Services\Gs1DigitalLink;

class SyncPublicationGtin
{
    public function __construct(private readonly Gs1DigitalLink $gs1) {}

    /**
     * Mirrors the freshly published version's GTIN onto its publication and
     * maintains the canonical GS1 Digital Link alias for the product.
     *
     * `gtin` is non-unique routing metadata: one product on N channels yields N
     * publications sharing a GTIN, so every publication carries it. The GS1
     * Digital Link `<base>/01/<gtin>` is, by contrast, product-scoped — a single
     * URL for all those channels — and cannot live in the UNIQUE
     * `alias_identifier` of more than one row. It is therefore stamped onto
     * exactly one canonical publication (the lowest `channel_id` for the GTIN,
     * mirroring `PublicationResolver::findByGtin`'s undesignated fallback) and
     * cleared from any sibling that still holds it, so the QR carrier on a
     * non-canonical channel falls back to its own per-channel passport URL.
     *
     * A GTIN outside the public route's grammar is stored as routing metadata but
     * never aliased: the link it would produce cannot resolve, and a carrier must
     * not encode a dead URL.
     *
     * Query-builder writes (not Eloquent saves) keep this event-free: it must
     * neither re-fire PublicationPublished nor touch any immutable version row.
     */
    public function handle(PublicationPublished $event): void
    {
        $gtin = $event->version->payload['identifier']['gtin'] ?? null;

        if ($gtin === null || $gtin === '') {
            return;
        }

        $publication = $event->publication;

        $model = PublicationProxy::modelClass();

        $model::query()->whereKey($publication->id)->update(['gtin' => $gtin]);

        $canonical = $model::query()
            ->where('gtin', $gtin)
            ->where('type', $publication->type)
            ->with('channel')
            ->orderBy('channel_id')
            ->first();

        if ($canonical === null) {
            return;
        }

        if (! $this->gs1->isWellFormed($gtin)) {
            return;
        }

        $link = $this->gs1->build($gtin, $canonical->channel->code);

        $model::query()
            ->where('gtin', $gtin)
            ->where('type', $publication->type)
            ->whereKeyNot($canonical->id)
            ->where('alias_identifier', $link)
            ->update(['alias_identifier' => null]);

        $model::query()->whereKey($canonical->id)->update(['alias_identifier' => $link]);
    }
}
