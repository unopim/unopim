<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Command;
use Webkul\MagicAI\Enums\AiProvider;
use Webkul\MagicAI\Models\MagicAIPlatform;
use Webkul\MagicAI\Repository\MagicAIPlatformRepository;
use Webkul\MagicAI\Services\ManagedPlatform;
use Webkul\MagicAI\Validator\PlatformValidator;

use function Laravel\Prompts\error;

/**
 * Shared dependencies and output helpers of the Magic AI platform commands.
 */
abstract class PlatformCommand extends Command
{
    protected MagicAIPlatformRepository $platformRepository;

    protected PlatformValidator $platformValidator;

    protected ManagedPlatform $managedPlatform;

    /**
     * Execute the console command.
     */
    public function handle(MagicAIPlatformRepository $platformRepository, PlatformValidator $platformValidator, ManagedPlatform $managedPlatform): int
    {
        $this->platformRepository = $platformRepository;
        $this->platformValidator = $platformValidator;
        $this->managedPlatform = $managedPlatform;

        return $this->perform();
    }

    abstract protected function perform(): int;

    protected function describe(MagicAIPlatform $platform): string
    {
        return trans('admin::app.configuration.platform.command.platform-option', [
            'label'    => $platform->label,
            'provider' => AiProvider::tryFrom($platform->provider)?->label() ?? $platform->provider,
        ]);
    }

    protected function flag(string $name): bool
    {
        return $this->hasOption($name) && (bool) $this->option($name);
    }

    protected function yesNo(bool $value): string
    {
        return trans($value ? 'admin::app.common.yes' : 'admin::app.common.no');
    }

    protected function enabledLabel(bool $value): string
    {
        return trans($value ? 'admin::app.common.enable' : 'admin::app.common.disable');
    }

    protected function missingOption(string $option): null
    {
        error(trans('admin::app.configuration.platform.command.missing-option', ['option' => $option]));

        return null;
    }
}
