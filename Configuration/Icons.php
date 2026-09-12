<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-bridge2cleverreach' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:bridge2cleverreach/Resources/Public/Icons/Extension.svg',
    ],
    'bridge2cleverreach-subscribeform' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:bridge2cleverreach/Resources/Public/Icons/bridge2cleverreach-subscribeform.svg',
    ],
    'bridge2cleverreach-unsubscribeform' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:bridge2cleverreach/Resources/Public/Icons/bridge2cleverreach-unsubscribeform.svg',
    ],
];
