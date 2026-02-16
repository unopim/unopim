<?php

namespace Webkul\ChannelConnector\ValueObjects;

class SyncResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $externalId = null,
        public readonly string $action = 'skipped',
        public readonly array $errors = [],
        public readonly ?string $dataHash = null,
    ) {}

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'external_id' => $this->externalId,
            'action'      => $this->action,
            'errors'      => $this->errors,
            'data_hash'   => $this->dataHash,
        ];
    }
}
