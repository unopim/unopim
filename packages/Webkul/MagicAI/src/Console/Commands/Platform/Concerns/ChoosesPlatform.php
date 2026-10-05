<?php

namespace Webkul\MagicAI\Console\Commands\Platform\Concerns;

use Webkul\MagicAI\Models\MagicAIPlatform;

use function Laravel\Prompts\error;
use function Laravel\Prompts\select;

/**
 * Resolves the platform a command acts on from its `id` argument.
 */
trait ChoosesPlatform
{
    /**
     * The platform named by the `id` argument, or chosen from a list when
     * none is given interactively.
     */
    protected function choosePlatform(): ?MagicAIPlatform
    {
        $id = $this->argument('id');

        if ($id === null && ! $this->input->isInteractive()) {
            error(trans('admin::app.configuration.platform.command.missing-argument', ['argument' => 'id']));

            return null;
        }

        if ($id === null) {
            $platforms = $this->platformRepository->forConsole();

            if ($platforms->isEmpty()) {
                error(trans('admin::app.configuration.platform.command.no-platforms'));

                return null;
            }

            $id = select(
                label: trans('admin::app.configuration.platform.command.select-platform'),
                options: $platforms->mapWithKeys(fn (MagicAIPlatform $platform): array => [
                    $platform->id => $this->describe($platform),
                ])->all(),
                scroll: 10,
            );
        }

        $platform = $this->platformRepository->find((int) $id);

        if (! $platform) {
            error(trans('admin::app.configuration.platform.message.not-found'));
        }

        return $platform;
    }
}
