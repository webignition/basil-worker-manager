<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__ . '/src')
    ->in(__DIR__ . '/tests')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        '@PhpCsFixer' => true,
        'concat_space' => [
            'spacing' => 'one',
        ],
        'trailing_comma_in_multiline' => false,
        'php_unit_internal_class' => false,
        'php_unit_test_class_requires_covers' => false,
        'class_definition' => [
            'multi_line_extends_each_single_line' => true,
        ],
    ])
    ->setFinder($finder)
;
