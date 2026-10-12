<?php

namespace Webkul\Publication\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
    case Redacted = 'redacted';

    /**
     * Translated label for the status.
     */
    public function label(): string
    {
        return trans('publication::app.publications.status.'.$this->value);
    }

    /**
     * Whether a new version may be minted. Withdrawn and Redacted are sticky:
     * publishing into either would leave the version unreachable behind the
     * public tombstone, so it has to be an explicit transition first.
     */
    public function acceptsNewVersions(): bool
    {
        return $this === self::Draft || $this === self::Published;
    }

    /**
     * The same rule as a set-based `whereIn`, for filtering rows without hydrating them.
     *
     * @return list<string>
     */
    public static function publishable(): array
    {
        return [self::Draft->value, self::Published->value];
    }

    /**
     * Withdrawn and Redacted both resolve on purpose — a 404 here would let a
     * caller infer a passport once existed; only Draft is invisible. Redacted
     * content is a null payload (tombstone), not absence of the route.
     */
    public function isPubliclyResolvable(): bool
    {
        return $this !== self::Draft;
    }
}
