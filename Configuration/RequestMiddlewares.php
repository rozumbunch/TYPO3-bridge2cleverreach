<?php
return [
    'frontend' => [
        'bridge2cleverreach/headless-api' => [
            'target' => \Rozumbunch\Bridge2Cleverreach\Middleware\RequestMiddleware::class,
            'after' => [
                'typo3/cms-frontend/site',
            ],
            'before' => [
                'typo3/cms-frontend/eid',
                'typo3/cms-frontend/tsfe',
            ],
        ],
    ],
];
