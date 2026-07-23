<?php

namespace Webkul\Publication\DataTransferObjects;

readonly class PublicationType
{
    public function __construct(
        public string $code,
        public string $label,
        public string $payloadBuilder,
        public string $template,
        public string $requiredGroup,
        public string $routePrefix,
    ) {}

    /**
     * @param  array{label: string, payload_builder: string, template: string, required_group: string, route_prefix: string}  $config
     */
    public static function fromConfig(string $code, array $config): self
    {
        return new self(
            code: $code,
            label: $config['label'],
            payloadBuilder: $config['payload_builder'],
            template: $config['template'],
            requiredGroup: $config['required_group'],
            routePrefix: $config['route_prefix'],
        );
    }
}
