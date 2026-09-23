<?php

namespace Webkul\MagicAI\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;

#[Signature('unopim:magic-ai:managed-platform')]
#[Description('Create or refresh the managed Magic AI platform from the MAGIC_AI_MANAGED_* environment settings')]
class ProvisionManagedPlatform extends Command
{
    /**
     * Create the managed platform, or bring an existing one back to the
     * configured endpoint and model list. It becomes the default only when
     * no other platform already is.
     */
    public function handle(ManagedPlatform $managedPlatform, MagicAIPlatformRepository $platformRepository): int
    {
        if (! $managedPlatform->isConfigured() || $managedPlatform->models() === []) {
            $this->components->error(trans('admin::app.configuration.platform.message.managed-not-configured'));

            return self::FAILURE;
        }

        $attributes = [
            'provider' => $managedPlatform->provider(),
            'api_url'  => $managedPlatform->apiUrl(),
            'models'   => implode(',', $managedPlatform->models()),
            'extras'   => null,
        ];

        $existing = $platformRepository->findWhere(['provider' => $managedPlatform->provider()])
            ->first(fn (MagicAIPlatform $platform): bool => $managedPlatform->isManagedPlatform($platform));

        if ($existing) {
            $platformRepository->update($attributes, $existing->id);
        } else {
            $platformRepository->create($attributes + [
                'label'      => $managedPlatform->label(),
                'api_key'    => $managedPlatform->apiKey(),
                'status'     => true,
                'is_default' => $platformRepository->getDefault() === null,
            ]);
        }

        $this->components->info(trans('admin::app.configuration.platform.message.managed-provisioned', [
            'label' => $existing->label ?? $managedPlatform->label(),
        ]));

        return self::SUCCESS;
    }
}
