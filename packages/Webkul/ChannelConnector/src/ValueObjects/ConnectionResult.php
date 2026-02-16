<?php

namespace Webkul\ChannelConnector\ValueObjects;

class ConnectionResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly array $channelInfo = [],
        public readonly array $errors = [],
    ) {}

    public function toArray(): array
    {
        return [
            'success'      => $this->success,
            'message'      => $this->message,
            'channel_info' => $this->channelInfo,
            'errors'       => $this->errors,
        ];
    }
}
