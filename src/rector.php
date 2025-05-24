<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/ui/templates',
    ])
    ->withSkipPath([
        __DIR__ . '/app/lib/Xhb/Model/Constants.php',
        __DIR__ . '/app/vendor',
    ])
    ->withPhp74Sets()
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::TYPE_DECLARATION,
    ])
    ->withParallel(180)
    //->withoutParallel()
;
