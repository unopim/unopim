<?php

namespace Webkul\Publication\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return 'publication::app.publications.status.'.$this->value;
    }

    /**
     * Withdrawn returns true on purpose: a withdrawn passport must still
     * resolve as a tombstone. Only a draft is invisible.
     */
    public function isPubliclyResolvable(): bool
    {
        return $this !== self::Draft;
    }
}
