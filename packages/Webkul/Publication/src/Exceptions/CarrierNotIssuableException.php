<?php

namespace Webkul\Publication\Exceptions;

use RuntimeException;
use Webkul\Publication\Models\Publication;
use Webkul\Publication\Models\PublicationRelease;

/**
 * Thrown when a carrier is requested for a publication or release that must not go into print.
 * `$releaseRedacted` tells the two refusals apart, so a caller can explain which one applied.
 */
class CarrierNotIssuableException extends RuntimeException
{
    private function __construct(string $message, public readonly bool $releaseRedacted)
    {
        parent::__construct($message);
    }

    public static function notPublished(Publication $publication): self
    {
        return new self('Publication '.$publication->id.' is '.$publication->status->value.'; only a published publication can be issued a carrier.', false);
    }

    public static function releaseRedacted(PublicationRelease $release): self
    {
        return new self('Release '.$release->id.' holds a redacted version; its carrier would only ever land on a tombstone.', true);
    }
}
