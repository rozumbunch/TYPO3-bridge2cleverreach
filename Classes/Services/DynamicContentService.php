<?php

namespace Rozumbunch\Bridge2Cleverreach\Services;

use Doctrine\DBAL\ParameterType;
use Rozumbunch\Bridge2Cleverreach\Domain\Repository\ContentElementsRepository;
use Rozumbunch\Bridge2Cleverreach\Utility\DataBaseUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;

class DynamicContentService
{
	/**
	 * @param $pageRootUid
	 * @return array|\mixed[][]
	 * @throws \Doctrine\DBAL\Exception
	 */
	public static function getDynamicContentFilters($pageRootUid)
	{
		$table = DataBaseUtility::SETTINGS_TABLE;
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable($table);

		$allTypes = $queryBuilder->select('type')
			->from($table)
			->where(
				$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageRootUid, ParameterType::INTEGER)),
				$queryBuilder->expr()->neq('type', $queryBuilder->createNamedParameter(ContentElementsRepository::SOURCE_PAGE_TYPE))
			)->executeQuery()->fetchAllAssociative();

		if(count($allTypes) > 0) {

			$addCustomTypes = false;
			foreach ($allTypes as $key => $type) {
				if($type['type'] == 'custom') {
					$addCustomTypes = true;
					unset($allTypes[$key]);
				}
			}

			if($addCustomTypes) {
				$customTypes = $queryBuilder->select('data')
					->from($table)
					->where(
						$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageRootUid, ParameterType::INTEGER)),
						$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('custom', ParameterType::STRING)),
					)->executeQuery()->fetchAllAssociative();
				if(count($customTypes) > 0) {
					$customTypesArray = explode(',', $customTypes[0]['data']);
					foreach ($customTypesArray as $customType) {
						$allTypes[]['type'] = $customType;
					}
				}
			}
		} else {
			return [];
		}

		return $allTypes;
	}

	/**
	 * @return array
	 */
	public static function getCustomDynamicContents(): array
	{
		$customDynamicContents = [];
		$dynamicContents = self::getAllDynamicContentsTypes();
		if(!empty($dynamicContents)) {
			foreach ($dynamicContents as $dynamicContent) {
				if(isset($dynamicContent['type']) && $dynamicContent['type'] != 'news' && $dynamicContent['type'] != 'content-elements') {
					$customDynamicContents[$dynamicContent['type']] = $dynamicContent;
				}
			}
		}

		return $customDynamicContents;
	}

	/**
	 * @param $pageRootUid
	 * @return \mixed[][]
	 * @throws \Doctrine\DBAL\Exception
	 */
	public static function getSelectedCustomDynamicContents($pageRootUid)
	{
		$table = DataBaseUtility::SETTINGS_TABLE;
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable($table);

		return $queryBuilder->select('data')
			->from($table)
			->where(
				$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageRootUid, ParameterType::INTEGER)),
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('custom', ParameterType::STRING)),
			)->executeQuery()->fetchAllAssociative();

	}

	/**
	 * @return array
	 */
	public static function getAllDynamicContentsTypes(): array
	{
		$config = GeneralUtility::makeInstance(ConfigurationManager::class)
			->getConfiguration(
				ConfigurationManager::CONFIGURATION_TYPE_FULL_TYPOSCRIPT,
				'bridge2cleverreach'
			);

		$typoscript = GeneralUtility::removeDotsFromTS($config);

		return isset($typoscript['plugin']['tx_bridge2cleverreach']['dynamicContents']) ? $typoscript['plugin']['tx_bridge2cleverreach']['dynamicContents'] : [];
	}

	/**
	 * The dynamicContents entry (e.g. detailPid, allowedCTypes) configured
	 * in setup.typoscript for a given type.
	 *
	 * @return array<string, mixed>
	 */
	public static function getDynamicContentConfig(string $type): array
	{
		foreach (self::getAllDynamicContentsTypes() as $dynamicContent) {
			if (($dynamicContent['type'] ?? null) === $type) {
				return $dynamicContent;
			}
		}

		return [];
	}

	/**
	 * @param $selectedContents
	 * @param $pid
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	public static function setSelectedCustomDynamicContentsForApiCleverReach($selectedContents, $pid): void
	{
		$recordExist = self::checkForSettingsRecordExist($pid);
		if(empty($selectedContents) && $recordExist){
			self::deleteSelectedCustomDynamicContentsForApiCleverReach($pid);
		}elseif($recordExist){
			self::updateSelectedCustomDynamicContentsForApiCleverReach($selectedContents, $pid);
		} else {
			$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
			$queryBuilder->insert(DataBaseUtility::SETTINGS_TABLE)
				->values([
					'type' => 'custom',
					'data' => $selectedContents,
					'pid' => $pid,
				])->executeStatement();
		}
	}

	/**
	 * @param $selectedContents
	 * @param $pid
	 * @return void
	 */
	public static function updateSelectedCustomDynamicContentsForApiCleverReach($selectedContents, $pid): void
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$queryBuilder->update(DataBaseUtility::SETTINGS_TABLE)
			->set('data', $selectedContents)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('custom')),
				$queryBuilder->expr()->eq('pid', $pid),
			)->executeStatement();
	}

	/**
	 * @param $pid
	 * @return void
	 */
	public static function deleteSelectedCustomDynamicContentsForApiCleverReach($pid): void
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$queryBuilder->delete(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('custom')),
				$queryBuilder->expr()->eq('pid', $pid),
			)->executeStatement();
	}

	/**
	 * @param $pid
	 * @return bool
	 * @throws \Doctrine\DBAL\Exception
	 */
	public static function checkForSettingsRecordExist($pid): bool
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$rows = $queryBuilder->select('*')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter('custom')),
				$queryBuilder->expr()->eq('pid', $pid),
			)
			->executeQuery()->fetchAllAssociative();

		return count($rows) > 0;
	}

}