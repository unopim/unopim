<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Webkul\MagicAI\Console\Commands\Platform\Concerns\ChoosesPlatform;
use Webkul\MagicAI\Models\MagicAIPlatform;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Signature('unopim:magic-ai:platform:default
    {id? : ID of the platform to make the default}')]
#[Description('Make a Magic AI platform the default one')]
class SetDefaultPlatform extends PlatformCommand
{
    use ChoosesPlatform;

    protected function perform(): int
    {
        $platform = $this->choosePlatform();

        if (! $platform instanceof MagicAIPlatform) {
            return self::FAILURE;
        }

        if (! $platform->status) {
            error(trans('admin::app.configuration.platform.message.default-requires-enabled'));

            return self::FAILURE;
        }

        $this->platformRepository->makeDefault($platform->id);

        info(trans('admin::app.configuration.platform.command.default-set', ['label' => $platform->label]));

        return self::SUCCESS;
    }
}
