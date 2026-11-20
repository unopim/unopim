<?php

namespace Webkul\Admin\Http\Requests\MagicAI\Concerns;

use Webkul\MagicAI\Repository\MagicAIPlatformRepository;

trait AuthorizesPlatformCredentials
{
    /**
     * Whether the submission reuses a saved platform's key: a masked key plus the platform id.
     */
    public function usesStoredPlatformKey(): bool
    {
        return app(MagicAIPlatformRepository::class)
            ->findForMaskedKey($this->input('api_key'), $this->input('id')) !== null;
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
