<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = (new Finder())
    ->in(__DIR__)
    ->exclude([
        'var',
        'vendor',
        'public/build',
    ])
    ->notPath([
        'config/bundles.php',
        'config/reference.php',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    // Désactive l'avertissement de version PHP 8.5 vs 8.4
    ->setUnsupportedPhpVersionAllowed(true)
    ->setRules([
        // Nouvelles nomenclatures exigées par PHP-CS-Fixer 3.65+
        '@PER-CS2x0' => true,
        '@PER-CS2x0:risky' => true,
        '@PHP8x3Migration' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'concat_space' => ['spacing' => 'one'],
        'native_function_invocation' => false,
    ])
    ->setFinder($finder);
