<?php

namespace Webkul\Publication\Support;

use Webkul\Core\Repositories\ChannelRepository;

class PublicationChannelOptions
{
    public function __construct(private readonly ChannelRepository $channels) {}

    /**
     * Channels shaped as `{title, value}` for the `gs1_passport_channel` system
     * settings select. The config field-type view binds `track-by="value"` /
     * `label-by="title"`, which the repository's own `code`/`label` option shape
     * does not satisfy, so this adapter exists rather than reusing it directly.
     *
     * @return list<array{title: string, value: string}>
     */
    public function toSettingsOptions(): array
    {
        return $this->channels->all()->map(fn ($channel): array => [
            'title' => $channel->name ?: "[{$channel->code}]",
            'value' => $channel->code,
        ])->values()->all();
    }
}
