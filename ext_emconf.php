<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Bridge to cleverreach',
    'description' => 'Api bridge to cleverreach',
    'category' => 'plugin',
    'author' => 'Rozumbunch',
    'author_email' => 'contact@rozumbunch.de',
    'state' => 'stable',
    'version' => '0.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-14.0.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Rozumbunch\\Bridge2Cleverreach\\' => 'Classes/',
        ],
    ],
];
