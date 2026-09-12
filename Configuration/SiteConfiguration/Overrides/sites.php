<?php

$GLOBALS['SiteConfiguration']['site']['columns']['cleverreachClientId'] = [
    'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachClientId.label',
    'config' => [
        'type' => 'input',
        'size' => '30',
        'default' => '',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['cleverreachClientSecret'] = [
    'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachClientSecret.label',
    'config' => [
        'type' => 'input',
        'size' => '30',
        'default' => '',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['cleverreachGroup'] = [
    'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachGroup.label',
    'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachGroup.description',
    'config' => [
        'type' => 'input',
        'size' => '30',
        'default' => '',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['cleverreachGroupName'] = [
    'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachGroupName.label',
    'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachGroupName.description',
    'config' => [
        'type' => 'input',
        'size' => '30',
        'default' => '',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['cleverreachDoubleOptInMailId'] = [
    'label' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachDoubleOptInMailId.label',
    'description' => 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.cleverreachDoubleOptInMailId.description',
    'config' => [
        'type' => 'input',
        'size' => '30',
        'default' => '',
        'eval' => 'trim',
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem']
    .= ',--div--;LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_db.xlf:site.tab.cleverreach,cleverreachClientId,cleverreachClientSecret,cleverreachGroup,cleverreachGroupName,cleverreachDoubleOptInMailId';
