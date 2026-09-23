<?php

namespace Rozumbunch\Bridge2Cleverreach\Utility;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataBaseUtility
{
	const SETTINGS_TABLE = 'tx_bridge2cleverreach_domain_model_dynamic_content_settings';

	public static function getQueryBuilderForTable($tableName)
	{
		return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
	}
}