<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Command;

class UnoPimVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Displays current version of UnoPim installed';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->comment(core()->version());
    }
}
