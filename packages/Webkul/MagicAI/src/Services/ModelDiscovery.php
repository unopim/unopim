<?php

namespace Webkul\MagicAI\Services;

use Webkul\MagicAI\Enums\AiProvider;

class ModelDiscovery
{
    /**
     * List the models a provider serves on the given credentials, with the
     * base URL that answered.
     *
     * @return array{models: list<string>, api_url: string}
     *
     * @throws \RuntimeException
     */
    public function discover(AiProvider $provider, ?string $apiKey, ?string $apiUrl): array
    {
        return $provider->discoverModels($apiKey, $apiUrl);
    }
}
