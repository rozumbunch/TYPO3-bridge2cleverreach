<?php

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Rozumbunch\Bridge2Cleverreach\Controller\CleverreachController;


defined('TYPO3') or die();

(static function () {

    ExtensionUtility::configurePlugin(
        'Bridge2Cleverreach',
        'Subscribeform',
        [
            CleverreachController::class => 'subscribe',
        ],
        [
            CleverreachController::class => 'subscribe',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );

    ExtensionUtility::configurePlugin(
        'Bridge2Cleverreach',
        'Unsubscribeform',
        [
            CleverreachController::class => 'unsubscribe',
        ],
        [
            CleverreachController::class => 'unsubscribe',
        ],
        ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
    );
})();
