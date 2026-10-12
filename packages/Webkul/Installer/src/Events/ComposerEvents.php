<?php

namespace Webkul\Installer\Events;

use Symfony\Component\Console\Output\ConsoleOutput;

class ComposerEvents
{
    /**
     * Post create project.
     */
    public static function postCreateProject(): void
    {
        $output = new ConsoleOutput;

        $output->writeln(file_get_contents(__DIR__.'/../Templates/on-boarding.php'));
    }
}
