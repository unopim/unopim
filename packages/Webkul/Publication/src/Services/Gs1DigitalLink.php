<?php

namespace Webkul\Publication\Services;

use Webkul\Publication\Models\Publication;

/**
 * Builds the GS1 Digital Link (`<base>/01/<gtin>`) a printed carrier resolves
 * through.
 *
 * The link is derived, never trusted from storage: `publications.alias_identifier`
 * records only which publication owns the GTIN, and it is stamped once at publish
 * time — so a later change to the passport base url (or to APP_URL) would leave
 * every previously printed row pointing at a host that no longer serves the app.
 */
class Gs1DigitalLink
{
    /**
     * The GTIN grammar the public `/01/{gtin}` route matches. A value outside it
     * yields a link that cannot route, so no carrier may encode one.
     */
    public const GTIN_PATTERN = '[0-9]{8,14}';

    public function isWellFormed(?string $gtin): bool
    {
        return $gtin !== null && preg_match('/^'.self::GTIN_PATTERN.'$/', $gtin) === 1;
    }

    /**
     * Lot (AI 10) and serial (AI 21) values: 1 to 20 characters from the GS1 82-character set.
     * Null means the qualifier is absent, which is well-formed.
     */
    public function isWellFormedQualifier(?string $value): bool
    {
        return $value === null || preg_match('/^[A-Za-z0-9!"%&\'()*+,\-.\/:;<=>?_]{1,20}$/', $value) === 1;
    }

    /**
     * The current link for a publication, or null when it does not own the GTIN
     * or the GTIN cannot route.
     */
    public function for(Publication $publication): ?string
    {
        if ($publication->alias_identifier === null || ! $this->isWellFormed($publication->gtin)) {
            return null;
        }

        return $this->build($publication->gtin, $publication->channel?->code);
    }

    public function build(string $gtin, ?string $channelCode = null): string
    {
        $base = core()->getConfigData('general.publication.settings.base_url', $channelCode)
            ?: config('app.url');

        return rtrim((string) $base, '/').'/01/'.$gtin;
    }
}
