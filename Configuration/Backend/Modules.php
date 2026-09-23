<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$modules = [
    'web_dynamicContent' => [
        'parent' => 'web',
        'position' => ['after' => 'ts'],
        'access' => 'user',
        'iconIdentifier' => 'be_modul_dynamiccontents',
        'path' => '/module/web/dynamicContent',
        'labels' => [
            'title' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContent.title',
            'shortDescription' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContent.shortDescription',
            'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContent.description',
        ],
        'appearance' => [
            'dependsOnSubmodules' => true,
        ],
        'showSubmoduleOverview' => true,
        'routes' => [
            '_default' => [
                'target' => \Rozumbunch\Bridge2Cleverreach\Backend\BackendCleverReachDynamicContentController::class . '::overviewAction',
            ],
        ],
    ],
    'web_dynamicContentRegister' => [
        'parent' => 'web_dynamicContent',
        'access' => 'user',
        'iconIdentifier' => 'be_modul_dynamiccontent_register',
        'path' => '/module/web/dynamicContentRegister',
        'labels' => [
            'title' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentRegister.title',
            'shortDescription' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentRegister.shortDescription',
            'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentRegister.description',
        ],
        'extensionName' => 'Bridge2Cleverreach',
        'routes' => [
            '_default' => [
                'target' => \Rozumbunch\Bridge2Cleverreach\Backend\BackendCleverReachDynamicContentController::class . '::handleRequest',
            ],
        ],
    ],
    'web_dynamicContentElements' => [
        'parent' => 'web_dynamicContent',
        'access' => 'user',
        'iconIdentifier' => 'be_modul_dynamiccontent_elements',
        'path' => '/module/web/dynamicContentElementsCreate',
        'labels' => [
            'title' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentElements.title',
            'shortDescription' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentElements.shortDescription',
            'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentElements.description',
        ],
        'extensionName' => 'Bridge2Cleverreach',
        'routes' => [
            '_default' => [
                'target' => \Rozumbunch\Bridge2Cleverreach\Backend\BackendCleverReachDynamicContentController::class . '::handleRequest',
            ],
        ],
    ],
    'web_dynamicContentCustom' => [
        'parent' => 'web_dynamicContent',
        'access' => 'user',
        'iconIdentifier' => 'be_modul_dynamiccontent_custom',
        'path' => '/module/web/dynamicContentCustom',
        'labels' => [
            'title' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentCustom.title',
            'shortDescription' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentCustom.shortDescription',
            'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentCustom.description',
        ],
        'extensionName' => 'Bridge2Cleverreach',
        'routes' => [
            '_default' => [
                'target' => \Rozumbunch\Bridge2Cleverreach\Backend\BackendCleverReachDynamicContentController::class . '::handleRequest',
            ],
        ],
    ],
];

// Only "News" lists/selects georgringer/news records, so only that module is
// gated on the extension being installed.
if (ExtensionManagementUtility::isLoaded('news')) {
    $modules['web_dynamicContentNews'] = [
        'parent' => 'web_dynamicContent',
        'access' => 'user',
        'iconIdentifier' => 'be_modul_dynamiccontent_news',
        'path' => '/module/web/dynamicContentCreate',
        'labels' => [
            'title' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentNews.title',
            'shortDescription' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentNews.shortDescription',
            'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:module.dynamicContentNews.description',
        ],
        'extensionName' => 'Bridge2Cleverreach',
        'routes' => [
            '_default' => [
                'target' => \Rozumbunch\Bridge2Cleverreach\Backend\BackendCleverReachDynamicContentController::class . '::handleRequest',
            ],
        ],
    ];
}

return $modules;
