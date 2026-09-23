<?php

namespace Rozumbunch\Bridge2Cleverreach\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Rozumbunch\Bridge2Cleverreach\Utility\DataBaseUtility;
use Rozumbunch\Bridge2Cleverreach\Utility\SitePageTree;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementsRepository
{
	public const TYPE = 'content-elements';
	public const SOURCE_PAGE_TYPE = 'content-elements-source-page';
	protected const TABLE = 'tt_content';

	/**
	 *
	 * @param int[] $pids
	 * @param string[] $allowedCTypes
	 * @return array<int, array<string, mixed>>
	 */
	public function getContentElementsByPids(array $pids, array $allowedCTypes): array
	{
		if (empty($pids) || empty($allowedCTypes)) {
			return [];
		}

		$queryBuilder = $this->getContentQueryBuilder();

		return $queryBuilder->select('uid', 'pid', 'header', 'subheader', 'bodytext', 'CType')
			->from(self::TABLE)
			->where(
				$queryBuilder->expr()->in('pid', $queryBuilder->createNamedParameter($pids, ArrayParameterType::INTEGER)),
				$queryBuilder->expr()->in('CType', $queryBuilder->createNamedParameter($allowedCTypes, ArrayParameterType::STRING))
			)
			->orderBy('pid')
			->addOrderBy('sorting')
			->executeQuery()
			->fetchAllAssociative();
	}

	/**
	 *
	 * @return array<int, array{uid: int, title: string}>
	 */
	public function getSourcePages($pid): array
	{
		$sourcePageUids = array_values(array_intersect(
			$this->getSourcePageUids($pid),
			SitePageTree::collectPageIds((int)$pid)
		));
		if (empty($sourcePageUids)) {
			return [];
		}

		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
		$queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
		$rows = $queryBuilder->select('uid', 'title')
			->from('pages')
			->where(
				$queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($sourcePageUids, ArrayParameterType::INTEGER))
			)
			->executeQuery()
			->fetchAllAssociative();

		$pagesByUid = [];
		foreach ($rows as $row) {
			$pagesByUid[(int)$row['uid']] = ['uid' => (int)$row['uid'], 'title' => (string)$row['title']];
		}

		$sourcePages = [];
		foreach ($sourcePageUids as $sourcePageUid) {
			if (isset($pagesByUid[$sourcePageUid])) {
				$sourcePages[] = $pagesByUid[$sourcePageUid];
			}
		}

		return $sourcePages;
	}

	/**
	 * @param $pid
	 * @return int[]
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function getSourcePageUids($pid): array
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);

		$row = $queryBuilder->select('data')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::SOURCE_PAGE_TYPE)),
				$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter((int)$pid, ParameterType::INTEGER)),
			)->executeQuery()->fetchAssociative();

		if (empty($row['data'])) {
			return [];
		}

		return array_map('intval', GeneralUtility::trimExplode(',', (string)$row['data'], true));
	}

	/**
	 * @param $sourcePageUids
	 * @param $pid
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function setSourcePageUids($sourcePageUids, $pid): void
	{
		if ($this->checkForSettingsRecordExist(self::SOURCE_PAGE_TYPE, $pid)) {
			$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
			$queryBuilder->update(DataBaseUtility::SETTINGS_TABLE)
				->set('data', $sourcePageUids)
				->where(
					$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::SOURCE_PAGE_TYPE)),
					$queryBuilder->expr()->eq('pid', $pid),
				)->executeStatement();
		} else {
			$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
			$queryBuilder->insert(DataBaseUtility::SETTINGS_TABLE)
				->values([
					'type' => self::SOURCE_PAGE_TYPE,
					'data' => $sourcePageUids,
					'pid' => $pid,
				])->executeStatement();
		}
	}

	/**
	 * @param int[] $uids
	 * @return array<int, array<string, mixed>>
	 */
	public function getContentElementsByUids(array $uids): array
	{
		if (empty($uids)) {
			return [];
		}

		$queryBuilder = $this->getContentQueryBuilder();

		return $queryBuilder->select('uid', 'pid', 'header', 'subheader', 'bodytext', 'CType')
			->from(self::TABLE)
			->where(
				$queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER))
			)
			->executeQuery()
			->fetchAllAssociative();
	}

	/**
	 * Explicitly selected content elements that lie on a stored source page
	 * and use an allowed content type. This is the module selection.
	 *
	 * @param string[] $allowedCTypes
	 * @return array<int, array<string, mixed>>
	 */
	public function getSelectedContentElementsForApiCleverReach(int $rootPageId, array $allowedCTypes): array
	{
		return $this->filterSelectableContentElements(
			$this->getStoredContentElementUids($rootPageId),
			$this->filterPageIdsToSite($rootPageId, $this->getSourcePageUids($rootPageId)),
			$allowedCTypes
		);
	}

	/**
	 * Endpoint payload: the selected records, plus every allowed content
	 * element on the stored source pages.
	 *
	 * @param string[] $allowedCTypes
	 * @return array<int, array<string, mixed>>
	 */
	public function getContentElementsForEndpoint(int $rootPageId, array $allowedCTypes): array
	{
		$selectedUids = $this->getStoredContentElementUids($rootPageId);
		$sourcePageIds = $this->filterPageIdsToSite($rootPageId, $this->getSourcePageUids($rootPageId));
		$fromPages = $this->getContentElementsByPids($sourcePageIds, $allowedCTypes);

		$selectedByUid = [];
		if ($selectedUids !== [] && $allowedCTypes !== []) {
			foreach ($this->getContentElementsByUids($selectedUids) as $element) {
				if (!in_array((string)$element['CType'], $allowedCTypes, true)) {
					continue;
				}
				$selectedByUid[(int)$element['uid']] = $element;
			}
		}

		$merged = [];
		$seen = [];
		foreach ($selectedUids as $uid) {
			if (!isset($selectedByUid[$uid])) {
				continue;
			}
			$merged[] = $selectedByUid[$uid];
			$seen[$uid] = true;
		}
		foreach ($fromPages as $element) {
			$uid = (int)$element['uid'];
			if (isset($seen[$uid])) {
				continue;
			}
			$merged[] = $element;
			$seen[$uid] = true;
		}

		return $merged;
	}

	/**
	 * @return int[]
	 */
	protected function getStoredContentElementUids(int $rootPageId): array
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$row = $queryBuilder->select('data')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::TYPE)),
				$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($rootPageId, ParameterType::INTEGER)),
			)->executeQuery()->fetchAssociative();

		if (empty($row['data'])) {
			return [];
		}

		return array_map('intval', GeneralUtility::trimExplode(',', (string)$row['data'], true));
	}

	/**
	 * Keeps content elements that are stored on one of the source pages and
	 * whose CType is allowed. Order follows $uids.
	 *
	 * @param int[] $uids
	 * @param int[] $sourcePageIds
	 * @param string[] $allowedCTypes
	 * @return array<int, array<string, mixed>>
	 */
	public function filterSelectableContentElements(array $uids, array $sourcePageIds, array $allowedCTypes): array
	{
		$uids = array_values(array_filter(array_map('intval', $uids)));
		$sourcePageIds = array_values(array_filter(array_map('intval', $sourcePageIds)));
		if ($uids === [] || $sourcePageIds === [] || $allowedCTypes === []) {
			return [];
		}

		$allowed = [];
		foreach ($this->getContentElementsByUids($uids) as $element) {
			if (!in_array((int)$element['pid'], $sourcePageIds, true)) {
				continue;
			}
			if (!in_array((string)$element['CType'], $allowedCTypes, true)) {
				continue;
			}
			$allowed[(int)$element['uid']] = $element;
		}

		$ordered = [];
		foreach ($uids as $uid) {
			if (isset($allowed[$uid])) {
				$ordered[] = $allowed[$uid];
			}
		}

		return $ordered;
	}

	/**
	 * Keeps only page uids that belong to the site root.
	 *
	 * @param int[]|string $pageIds
	 * @return int[]
	 */
	public function filterPageIdsToSite(int $rootPageId, array|string $pageIds): array
	{
		$requested = is_array($pageIds)
			? array_map('intval', $pageIds)
			: array_map('intval', GeneralUtility::trimExplode(',', $pageIds, true));
		$allowed = SitePageTree::collectPageIds($rootPageId);

		return array_values(array_intersect($requested, $allowed));
	}

	/**
	 * @param $uids
	 * @param $pid
	 * @return void
	 */
	public function updateSelectedContentElementUidsForApiCleverReach($uids, $pid): void
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$queryBuilder->update(DataBaseUtility::SETTINGS_TABLE)
			->set('data', $uids)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::TYPE)),
				$queryBuilder->expr()->eq('pid', $pid),
			)->executeStatement();
	}

	/**
	 * @param $uids
	 * @param $pid
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function setSelectedContentElementUidsForApiCleverReach($uids, $pid): void
	{
		if ($this->checkForSettingsRecordExist(self::TYPE, $pid)) {
			$this->updateSelectedContentElementUidsForApiCleverReach($uids, $pid);
		} else {
			$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
			$queryBuilder->insert(DataBaseUtility::SETTINGS_TABLE)
				->values([
					'type' => self::TYPE,
					'data' => $uids,
					'pid' => $pid,
				])->executeStatement();
		}
	}

	/**
	 * @param string $type
	 * @param $pid
	 * @return bool
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function checkForSettingsRecordExist(string $type, $pid): bool
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$rows = $queryBuilder->select('*')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter($type)),
				$queryBuilder->expr()->eq('pid', $pid),
			)
			->executeQuery()->fetchAllAssociative();

		return count($rows) > 0;
	}

	protected function getContentQueryBuilder()
	{
		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
		$queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

		return $queryBuilder;
	}
}
