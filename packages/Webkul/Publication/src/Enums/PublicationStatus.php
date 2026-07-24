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
     * Withdrawn and Redacted both resolve on purpose — a 404 here would let a
     * caller infer a passport once existed; only Draft is invisible. Redacted
     * content is a null payload (tombstone), not absence of the route.
     */
    public function isPubliclyResolvable(): bool
    {
        return $this !== self::Draft;
    }
}
