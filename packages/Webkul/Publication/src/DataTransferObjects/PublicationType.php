<?php

namespace Webkul\Publication\DataTransferObjects;

readonly class PublicationType
{
    public function __construct(
        public string $code,
        public string $label,
        public string $payloadBuilder,
        public string $template,
        public string $routePrefix,
        public ?string $jsonld = null,
        public ?string $gate = null,
    ) {}

    /**
     * @param  array{label: string, payload_builder: string, template: string, route_prefix: string, jsonld?: string|null, gate?: string|null}  $config
     */
    public static function fromConfig(string $code, array $config): self
    {
        return new self(
            code: $code,
            label: $config['label'],
            payloadBuilder: $config['payload_builder'],
            template: $config['template'],
            routePrefix: $config['route_prefix'],
            jsonld: $config['jsonld'] ?? null,
            gate: $config['gate'] ?? null,
        );
    }
}
