<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/',
    ])
    ->withSkip([
        '*/vendor/*',
        '*/node_modules/*',
    ])
    ->withAutoloadPaths([
        __DIR__ . '/vendor/squizlabs/php_codesniffer/autoload.php',
        __DIR__ . '/vendor/php-stubs/wordpress-stubs/wordpress-stubs.php',
    ])
    ->withTypeCoverageLevel(45)
    ->withDeadCodeLevel(44);
    // ->withPreparedSets(
    //     deadCode: true,
    //     codeQuality: true,
    //     typeDeclarations: true,
    //     privatization: true,
    //     naming: true,
    //     instanceOf: true,
    //     earlyReturn: true,
    //     strictBooleans: true,
    //     phpunitCodeQuality: true,
    // );
