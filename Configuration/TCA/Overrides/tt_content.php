<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$GLOBALS['TCA']['tt_content']['columns']['CType']['config']['itemGroups']['newsletter']
    ??= 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tt_content.CType.div.newsletter';

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_subscribeform.name',
        'value' => 'subscribeform',
        'icon' => 'bridge2cleverreach-subscribeform',
        'group' => 'newsletter',
        'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_subscribeform.description',
    ]
);

ExtensionManagementUtility::addPlugin(
    [
        'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_unsubscribeform.name',
        'value' => 'unsubscribeform',
        'icon' => 'bridge2cleverreach-unsubscribeform',
        'group' => 'newsletter',
        'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_unsubscribeform.description',
    ]
);

$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['subscribeform'] = 'bridge2cleverreach-subscribeform';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['unsubscribeform'] = 'bridge2cleverreach-unsubscribeform';


$tabGeneral = '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,--palette--;;general';
$pluginFlexform = '--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin, pi_flexform';
$tabAccess = '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;hidden, --palette--;;access';

$showItems = [
    $tabGeneral,
    '--palette--;;headers,',
    $pluginFlexform,
    $tabAccess
];

$showItemsSubscribe = [
    $tabGeneral,
    '--palette--;;headers, bodytext,',
    $pluginFlexform,
    $tabAccess
];

$columnsOverrides = [
    'subheader' => [
        'config' => [
            'type' => 'text',
            'size' => 30,
        ],
    ],
    'colPos' => [
        'displayCond' => 'FIELD:CType:=:dummy',
    ],
];

$GLOBALS['TCA']['tt_content']['types']['subscribeform'] = [
    'showitem' => implode(',', $showItemsSubscribe),
    'columnsOverrides' => array_replace_recursive($columnsOverrides, [
        'bodytext' => [
            'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:teaser',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
            ],
        ],
        'pi_flexform' => [
            'config' => [
                'ds' => 'FILE:EXT:bridge2cleverreach/Configuration/FlexForms/flexform_subscribeform.xml',
            ],
        ],
    ]),
];

$GLOBALS['TCA']['tt_content']['types']['unsubscribeform'] = [
    'showitem' => implode(',', $showItems),
    'columnsOverrides' => array_replace_recursive($columnsOverrides, [
        'pi_flexform' => [
            'config' => [
                'ds' => 'FILE:EXT:bridge2cleverreach/Configuration/FlexForms/flexform_unsubscribeform.xml',
            ],
        ],
    ]),
];
