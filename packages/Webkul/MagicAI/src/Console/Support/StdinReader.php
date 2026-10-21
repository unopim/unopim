<?php

namespace Webkul\MagicAI\Console\Support;

class StdinReader
{
    /**
     * Read one line from standard input, without its line ending.
     */
    public function readLine(): string
    {
        $line = defined('STDIN') ? fgets(STDIN) : false;

        return $line === false ? '' : trim($line);
    }
}
