<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/ui/templates',
    ])
    ->withSkip([
        __DIR__ . '/app/lib/Xhb/Model/Constants.php',
        __DIR__ . '/app/vendor',
    ])
    ->withFileExtensions(['php'])
    ->withPhpSets(php83: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
        SetList::PHP_83
    ])

    //->withFileExtensions(['phtml'])
    //->withRules([\Rector\Php54\Rector\Array_\LongArrayToShortArrayRector::class])

    ->withParallel(180)
    //->withoutParallel()
;
