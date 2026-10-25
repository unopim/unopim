<?php

namespace Webkul\Publication\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Webkul\Publication\Enums\PublicationStatus;
use Webkul\Publication\Exceptions\CarrierNotIssuableException;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationCarrierIssuance;
use Webkul\Publication\Models\PublicationProxy;
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

    /**
     * Records the issuance of a carrier for `$release`. Refused unless the publication is Published and the
     * release's own version is not redacted: a withdrawn or redacted (GDPR-erased) passport, or an erased
     * release, would put a code into print that can only ever land on a tombstone.
     *
     * Both checks run inside a transaction that holds the publication row lock. `Publisher::redactAll()` takes
     * the same lock and every status change writes that row, so neither a redaction nor a withdrawal can slip
     * in between the checks and the record. The release's versions are read with a shared lock as a whole,
     * not filtered on `redacted_at` in SQL, so a version redacted on its own (which writes only the version
     * row) cannot commit in between either: PostgreSQL only locks the rows a locking read returns.
     *
     * @throws CarrierNotIssuableException
     */
    public function issue(Publication $publication, PublicationRelease $release, ?int $issuedById = null): PublicationCarrierIssuance
    {
        if ((int) $release->publication_id !== (int) $publication->id) {
            throw new InvalidArgumentException('Release '.$release->id.' does not belong to publication '.$publication->id.'.');
        }

        return DB::transaction(function () use ($publication, $release, $issuedById): PublicationCarrierIssuance {
            $locked = PublicationProxy::modelClass()::query()->whereKey($publication->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== PublicationStatus::Published) {
                throw CarrierNotIssuableException::notPublished($locked);
            }

            if ($release->versions()->sharedLock()->pluck('redacted_at')->filter()->isNotEmpty()) {
                throw CarrierNotIssuableException::releaseRedacted($release);
            }

            return $locked->carrierIssuances()->create([
                'release_id'   => $release->id,
                'target'       => $this->targetFor($locked, $release),
                'format'       => 'svg',
                'issued_at'    => now(),
                'issued_by_id' => $issuedById,
            ]);
        });
    }
}
