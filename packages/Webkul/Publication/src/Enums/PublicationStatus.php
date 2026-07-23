<?php

namespace Webkul\Publication\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Withdrawn = 'withdrawn';
    case Redacted = 'redacted';

    public function label(): string
    {
        return trans('publication::app.publications.status.'.$this->value);
    }

    /**
     * Withdrawn and Redacted both return true on purpose: a withdrawn or
     * redacted passport must still resolve (a 404 here would let a caller
     * infer a passport once existed). Only a draft is invisible. This does
     * NOT mean the same content renders: Withdrawn keeps the last-published
     * payload, while a Redacted version's payload is structurally null (see
     * PublicationVersion::redact()), so the public renderer must treat it as
     * a tombstone with no content rather than displaying an empty object.
     */
    public function isPubliclyResolvable(): bool
    {
        return $this !== self::Draft;
    }
}
