<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/{src,tests}')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'strict_comparison' => true,
    ])
    ->setFinder($finder)
;
