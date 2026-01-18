<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_subscribeform.name',
        'subscribeform',
        'bridge2cleverreach-subscribeform'
    ],
    'CType',
    'bridge2cleverreach'
);

ExtensionManagementUtility::addPlugin(
    [
        'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:tx_bridge2cleverreach_unsubscribeform.name',
        'unsubscribeform',
        'bridge2cleverreach-unsubscribeform'
    ],
    'CType',
    'bridge2cleverreach'
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
    'showitem' => implode(',', $showItems),
    'columnsOverrides' => $columnsOverrides,
];

$GLOBALS['TCA']['tt_content']['types']['unsubscribeform'] = [
    'showitem' => implode(',', $showItems),
    'columnsOverrides' => $columnsOverrides,
];

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:bridge2cleverreach/Configuration/FlexForms/flexform_subscribeform.xml',
    'subscribeform'
);

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:bridge2cleverreach/Configuration/FlexForms/flexform_unsubscribeform.xml',
    'unsubscribeform'
);
