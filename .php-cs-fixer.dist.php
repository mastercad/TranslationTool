<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('node_modules')
    ->exclude('build')
    ->exclude('config')
    ->exclude('public')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'trailing_comma_in_multiline' => false
    ])
    ->setFinder($finder)
;
