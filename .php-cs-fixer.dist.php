<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setFinder($finder)
    ->setRules([
        '@PSR12' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays']],
        'no_trailing_whitespace' => true,
        'concat_space' => ['spacing' => 'one'],
        'octal_notation' => true,
    ]);
