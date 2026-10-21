<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Webkul\MagicAI\Console\Commands\Platform\Concerns\ChoosesPlatform;
use Webkul\MagicAI\Console\Commands\Platform\Concerns\CollectsPlatform;
use Webkul\MagicAI\Models\MagicAIPlatform;

#[Signature('unopim:magic-ai:platform:edit
    {id? : ID of the platform to edit}
    {--provider= : Provider code, e.g. concentrate}
    {--label= : Platform label}
    {--api-url= : API URL, empty for the provider default}
    {--models= : Comma-separated model IDs}
    {--azure-deployment= : Azure deployment name}
    {--azure-api-version= : Azure API version}
    {--default : Make it the default platform}
    {--disabled : Save it disabled}
    {--enabled : Save it enabled}
    {--managed : Turn the managed lock on}
    {--unmanaged : Turn the managed lock off}
    {--key-stdin : Read a new API key from the first line of standard input}')]
#[Description('Edit a Magic AI platform; an empty key keeps the stored one')]
class EditPlatform extends PlatformCommand
{
    use ChoosesPlatform, CollectsPlatform;

    protected function perform(): int
    {
        $platform = $this->choosePlatform();

        return $platform instanceof MagicAIPlatform ? $this->collectAndSave($platform) : self::FAILURE;
    }
}
