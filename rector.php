<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\DeclareStrictTypesRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/packages/Webkul',
    ])
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::EARLY_RETURN,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
        LaravelSetList::LARAVEL_IF_HELPERS,
        LaravelLevelSetList::UP_TO_LARAVEL_130,
    ])
    ->withSkip([
        __DIR__.'/packages/Webkul/*/src/Database/Migration',
        __DIR__.'/packages/Webkul/*/src/Database/Migrations',
        __DIR__.'/packages/Webkul/Admin',
        __DIR__.'/packages/Webkul/AdminApi',
        '*/tests/*',
        SafeDeclareStrictTypesRector::class,
        DeclareStrictTypesRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ]);
