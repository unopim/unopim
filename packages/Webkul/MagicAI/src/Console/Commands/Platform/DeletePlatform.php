<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Webkul\MagicAI\Console\Commands\Platform\Concerns\ChoosesPlatform;
use Webkul\MagicAI\Models\MagicAIPlatform;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;

#[Signature('unopim:magic-ai:platform:delete
    {id? : ID of the platform to delete}
    {--force : Delete without asking for confirmation}')]
#[Description('Delete a Magic AI platform, including a managed one')]
class DeletePlatform extends PlatformCommand
{
    use ChoosesPlatform;

    protected function perform(): int
    {
        $platform = $this->choosePlatform();

        if (! $platform instanceof MagicAIPlatform) {
            return self::FAILURE;
        }

        if ($platform->is_default) {
            error(trans('admin::app.configuration.platform.message.cannot-delete-default'));

            return self::FAILURE;
        }

        if (! $this->option('force')) {
            if (! $this->input->isInteractive()) {
                error(trans('admin::app.configuration.platform.command.force-required'));

                return self::FAILURE;
            }

            if (! confirm(trans('admin::app.configuration.platform.command.delete-confirm', ['label' => $platform->label]), default: false)) {
                info(trans('admin::app.configuration.platform.command.cancelled'));

                return self::SUCCESS;
            }
        }

        $this->platformRepository->delete($platform->id);

        info(trans('admin::app.configuration.platform.command.deleted', ['label' => $platform->label]));

        return self::SUCCESS;
    }
}
