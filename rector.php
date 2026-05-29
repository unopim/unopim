<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/packages',
        __DIR__.'/tests',
    ])
    ->withSkip([
        __DIR__.'/packages/*/src/Resources',
        __DIR__.'/packages/*/src/Database/Migration',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
    );
