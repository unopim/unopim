<?php

namespace Webkul\MagicAI\Console\Commands\Platform;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Webkul\MagicAI\Console\Commands\Platform\Concerns\CollectsPlatform;

#[Signature('unopim:magic-ai:platform:add
    {--provider= : Provider code, e.g. concentrate}
    {--label= : Platform label}
    {--api-url= : API URL, empty for the provider default}
    {--models= : Comma-separated model IDs}
    {--azure-deployment= : Azure deployment name}
    {--azure-api-version= : Azure API version}
    {--default : Make it the default platform}
    {--disabled : Save it disabled}
    {--unmanaged : Save it without the managed lock}
    {--key-stdin : Read the API key from the first line of standard input}')]
#[Description('Add a Magic AI platform, optionally managed, storing its API key encrypted in the database')]
class AddPlatform extends PlatformCommand
{
    use CollectsPlatform;

    protected function perform(): int
    {
        return $this->collectAndSave(null);
    }
}
