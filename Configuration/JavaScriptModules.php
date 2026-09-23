<?php

return [
    'dependencies' => [
        'backend',
    ],
    'tags' => [
        'backend.form',
    ],
    'imports' => [
        '@Rozumbunch/Bridge2Cleverreach/' => [
            'path' => 'EXT:bridge2cleverreach/Resources/Public/Javascript/',
        ],
    ]
];
