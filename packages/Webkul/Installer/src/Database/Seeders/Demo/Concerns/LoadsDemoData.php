<?php

namespace Webkul\Installer\Database\Seeders\Demo\Concerns;

use RuntimeException;

trait LoadsDemoData
{
    /**
     * Read a demo dataset shipped under `Database/Data/Demo`.
     *
     * @return array<string, mixed>
     */
    protected function demoData(string $name): array
    {
        $path = __DIR__.'/../../../Data/Demo/'.$name.'.php';

        if (! is_file($path)) {
            throw new RuntimeException('Demo dataset ['.$name.'] is missing.');
        }

        return require $path;
    }
}
