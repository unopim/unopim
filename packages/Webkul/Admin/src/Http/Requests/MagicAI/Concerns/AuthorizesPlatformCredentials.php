<?php

namespace Webkul\Admin\Http\Requests\MagicAI\Concerns;

use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;

trait AuthorizesPlatformCredentials
{
    /**
     * Whether the submission reuses a saved platform's key or settings: a
     * platform id with a masked or omitted key, the same rule the managed
     * platform path applies when it answers from the saved record.
     */
    public function usesStoredPlatformKey(): bool
    {
        $id = (int) $this->input('id');

        $apiKey = $this->input('api_key');

        return $id > 0
            && (! is_string($apiKey) || app(ManagedPlatform::class)->usesStoredKey($apiKey))
            && app(MagicAIPlatformRepository::class)->find($id) !== null;
    }

    /**
     * Reusing a saved key needs the right to edit platforms; a typed key needs
     * the right to create or edit one. Viewing platforms alone allows neither.
     */
    protected function canUsePlatformCredentials(): bool
    {
        if (! bouncer()->hasPermission('ai-agent.platform')) {
            return false;
        }

        if ($this->usesStoredPlatformKey()) {
            return bouncer()->hasPermission('ai-agent.platform.edit');
        }

        return bouncer()->hasPermission('ai-agent.platform.create')
            || bouncer()->hasPermission('ai-agent.platform.edit');
    }
}
