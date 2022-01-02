<?php

$finder = PhpCsFixer\Finder::create()
    ->exclude('bootstrap')
    ->exclude('resources')
    ->exclude('vendor')
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
