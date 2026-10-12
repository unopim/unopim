<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Displays current version of UnoPim installed')]
#[Signature('unopim:version')]
class UnoPimVersion extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->comment(core()->version());
    }
}
