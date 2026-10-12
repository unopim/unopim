<?php

namespace Webkul\Publication\Registry;

use Illuminate\Contracts\Config\Repository;
use InvalidArgumentException;
use Webkul\Publication\DataTransferObjects\PublicationType;

class PublicationTypeRegistry
{
    /** @var array<string, PublicationType>|null */
    private ?array $types = null;

    public function __construct(private readonly Repository $config) {}

    /**
     * @return array<string, PublicationType>
     */
    public function all(): array
    {
        return $this->types ??= collect($this->config->get('publication.types', []))
            ->map(fn (array $config, string $code): PublicationType => PublicationType::fromConfig($code, $config))
            ->all();
    }

    public function has(string $code): bool
    {
        return isset($this->all()[$code]);
    }

    public function get(string $code): PublicationType
    {
        return $this->all()[$code]
            ?? throw new InvalidArgumentException('Unknown publication type ['.$code.'].');
    }
}
