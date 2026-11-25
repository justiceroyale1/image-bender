<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    // Target PHP 8.4 explicitly (or call ->withPhpSets() with no args to read composer.json)
    ->withPhpSets(php84: true)
    // Automatically detects Laravel 13 (plus Livewire, Cashier, Faker) from composer.json
    ->withComposerBased(laravel: true)
    // Core code quality & type-safety sets (helps resolve PHPStan errors)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
    )
    // Laravel-specific code quality & collection improvements
    ->withSets([
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_TYPE_DECLARATIONS,
    ]);
