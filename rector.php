<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
    ])
    ->withPhpSets(php85: true)
    ->withComposerBased(
        symfony: true,
        twig: true,
        doctrine: true,
        phpunit: true,
    )
    ->withPreparedSets(symfonyCodeQuality: true)
    ->withTypeCoverageLevel(0);
