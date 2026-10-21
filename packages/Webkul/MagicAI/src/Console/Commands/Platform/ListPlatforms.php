<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;

use function Laravel\Prompts\info;
use function Laravel\Prompts\table;

#[Signature('unopim:magic-ai:platform:list')]
#[Description('List the Magic AI platforms')]
class ListPlatforms extends PlatformCommand
{
    protected function perform(): int
    {
        $platforms = $this->platformRepository->forConsole();

        if ($platforms->isEmpty()) {
            info(trans('admin::app.configuration.platform.command.no-platforms'));

            return self::SUCCESS;
        }

        table(
            headers: [
                trans('admin::app.configuration.platform.command.id'),
                trans('admin::app.configuration.platform.datagrid.label'),
                trans('admin::app.configuration.platform.datagrid.provider'),
                trans('admin::app.configuration.platform.datagrid.models'),
                trans('admin::app.configuration.platform.datagrid.default'),
                trans('admin::app.configuration.platform.datagrid.status'),
                trans('admin::app.configuration.platform.managed-badge'),
            ],
            rows: $platforms->map(fn (MagicAIPlatform $platform): array => [
                (string) $platform->id,
                $platform->label,
                AiProvider::tryFrom($platform->provider)?->label() ?? $platform->provider,
                str_replace(',', ', ', (string) $platform->models),
                $this->yesNo($platform->is_default),
                $this->enabledLabel($platform->status),
                $this->yesNo($platform->is_managed),
            ])->all(),
        );

        return self::SUCCESS;
    }
}
