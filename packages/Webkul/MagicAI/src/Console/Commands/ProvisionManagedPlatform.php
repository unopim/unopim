<?php

namespace Webkul\MagicAI\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;

#[Signature('unopim:magic-ai:managed-platform')]
#[Description('Create or refresh the managed Magic AI platform, storing its API key encrypted in the database')]
class ProvisionManagedPlatform extends Command
{
    /**
     * Create the managed platform, or bring an existing one back to the
     * configured endpoint and model list. The key is asked for rather than
     * taken as an option so it never lands in the process list or shell
     * history. It becomes the default only when no other platform already is.
     */
    public function handle(ManagedPlatform $managedPlatform, MagicAIPlatformRepository $platformRepository): int
    {
        if (! $managedPlatform->isConfigured()) {
            $this->components->error(trans('admin::app.configuration.platform.message.managed-not-configured'));

            return self::FAILURE;
        }

        $existing = $platformRepository->findOneWhere(['is_managed' => true]);

        $apiKey = $this->input->isInteractive()
            ? trim((string) $this->secret(trans('admin::app.configuration.platform.message.managed-key-prompt')))
            : '';

        if ($apiKey === '' && ! $existing) {
            $this->components->error(trans('admin::app.configuration.platform.message.managed-key-required'));

            return self::FAILURE;
        }

        $attributes = [
            'provider' => $managedPlatform->provider(),
            'api_url'  => $managedPlatform->apiUrl(),
            'models'   => implode(',', $managedPlatform->models()),
            'extras'   => null,
            'status'   => true,
        ];

        if ($apiKey !== '') {
            $attributes['api_key'] = $apiKey;
        }

        if ($platformRepository->getDefault() === null) {
            $attributes['is_default'] = true;
        }

        $platform = $existing ?? $platformRepository->getModel()->newInstance(['label' => $managedPlatform->label()]);

        $platform->fill($attributes)->forceFill(['is_managed' => true])->save();

        $this->components->info(trans('admin::app.configuration.platform.message.managed-provisioned', [
            'label' => $platform->label,
        ]));

        return self::SUCCESS;
    }
}
