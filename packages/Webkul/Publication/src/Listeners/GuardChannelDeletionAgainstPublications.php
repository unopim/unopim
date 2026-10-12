<?php

namespace Webkul\Publication\Listeners;

use RuntimeException;
use Webkul\Publication\Models\PublicationProxy;

/**
 * Same rationale as `GuardProductDeletionAgainstPublications`, for the
 * RESTRICT foreign key on `publications.channel_id`.
 */
class GuardChannelDeletionAgainstPublications
{
    public function handle(int $channelId): void
    {
        if (PublicationProxy::modelClass()::where('channel_id', $channelId)->exists()) {
            throw new RuntimeException(trans('publication::app.publications.channel-delete-blocked'));
        }
    }
}
