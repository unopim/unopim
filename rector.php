<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\StrictArrayParamDimFetchRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;
use RectorLaravel\Rector\MethodCall\ContainerBindConcreteWithClosureOnlyRector;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/packages/Webkul',
    ])
    ->withPhpSets(php84: true)
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
        AddOverrideAttributeToOverriddenMethodsRector::class,
        // Types Laravel container closures as `array $app` — fatal at boot ($app is the Application object).
        StrictArrayParamDimFetchRector::class,
        // Drops the explicit $abstract from singleton/bind calls; keep bindings explicit.
        ContainerBindConcreteWithClosureOnlyRector::class,
    ]);
