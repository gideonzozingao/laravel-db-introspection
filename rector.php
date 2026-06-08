<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])

    ->withPhpSets(php84: true)

    // ->withDeadCodeLevel(5)
    // ->withCodeQualityLevel(5)

    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
    )

    ->withSkip([
        __DIR__.'/vendor',
    ]);
