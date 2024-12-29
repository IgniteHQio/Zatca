<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in(__DIR__)
;

return (new Config())
    ->setRules([
        '@PhpCsFixer' => true,
        'echo_tag_syntax' => [
            'format' => 'short',
        ],
    ])
    ->setFinder($finder)
;
