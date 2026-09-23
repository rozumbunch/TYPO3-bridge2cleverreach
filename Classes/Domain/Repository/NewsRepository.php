<?php

namespace Rozumbunch\Bridge2Cleverreach\Domain\Repository;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Rozumbunch\Bridge2Cleverreach\Utility\DataBaseUtility;
use Rozumbunch\Bridge2Cleverreach\Utility\SitePageTree;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NewsRepository
{
	public const TYPE = 'news';
	protected const TABLE = 'tx_news_domain_model_news';

	/**
	 * News records stored on the root page or on a page below it.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function getAllNews(int $rootPageId): array
	{
		$pageIds = $this->collectPageIds($rootPageId);
		if ($pageIds === []) {
			return [];
		}

		$queryBuilder = $this->getNewsQueryBuilder();

		return $queryBuilder
			->select('*')
			->from(self::TABLE)
			->where(
				$queryBuilder->expr()->in(
					'pid',
					$queryBuilder->createNamedParameter($pageIds, ArrayParameterType::INTEGER)
				)
			)
			->orderBy('title')
			->executeQuery()
			->fetchAllAssociative();
	}

	/**
	 * @param int[] $uids
	 * @param int[] $pageIds
	 * @return array<int, array<string, mixed>>
	 */
	public function getNewsByUids(array $uids, array $pageIds = []): array
	{
		if (empty($uids)) {
			return [];
		}

		$queryBuilder = $this->getNewsQueryBuilder();
		$constraints = [
			$queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($uids, ArrayParameterType::INTEGER)),
		];
		if ($pageIds !== []) {
			$constraints[] = $queryBuilder->expr()->in(
				'pid',
				$queryBuilder->createNamedParameter($pageIds, ArrayParameterType::INTEGER)
			);
		}

		$rows = $queryBuilder->select('*')
			->from(self::TABLE)
			->where(...$constraints)
			->executeQuery()
			->fetchAllAssociative();

		$indexed = [];
		foreach ($rows as $row) {
			$indexed[(int)$row['uid']] = $row;
		}

		$ordered = [];
		foreach ($uids as $uid) {
			if (isset($indexed[$uid])) {
				$ordered[] = $indexed[$uid];
			}
		}

		return $ordered;
	}

	/**
	 * Selected news of one site root. Records outside that page tree are ignored.
	 *
	 * @return array<int, array<string, mixed>>
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function getSelectedNewsUidsForApiCleverReach(int $rootPageId): array
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);

		$row = $queryBuilder->select('news')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::TYPE)),
				$queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($rootPageId, ParameterType::INTEGER)),
			)->executeQuery()->fetchAssociative();

		if (empty($row['news'])) {
			return [];
		}

		$uids = array_map('intval', GeneralUtility::trimExplode(',', (string)$row['news'], true));

		return $this->getNewsByUids($uids, $this->collectPageIds($rootPageId));
	}

	/**
	 * Root page plus every non-deleted page below it.
	 *
	 * @return int[]
	 */
	protected function collectPageIds(int $rootPageId): array
	{
		return SitePageTree::collectPageIds($rootPageId);
	}

	/**
	 * @param $newsUids
	 * @param $pid
	 * @return void
	 */
	public function updateSelectedNewsUidsForApiCleverReach($newsUids, $pid): void
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$queryBuilder->update(DataBaseUtility::SETTINGS_TABLE)
			->set('news', $newsUids)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::TYPE)),
				$queryBuilder->expr()->eq('pid', $pid),
			)->executeStatement();
	}

	/**
	 * @param $newsUids
	 * @param $pid
	 * @return void
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function setSelectedNewsUidsForApiCleverReach($newsUids, $pid): void
	{
		if ($this->checkForSettingsRecordExist($pid)) {
			$this->updateSelectedNewsUidsForApiCleverReach($newsUids, $pid);
		} else {
			$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
			$queryBuilder->insert(DataBaseUtility::SETTINGS_TABLE)
				->values([
					'type' => self::TYPE,
					'news' => $newsUids,
					'pid' => $pid,
				])->executeStatement();
		}
	}

	/**
	 * @param $pid
	 * @return bool
	 * @throws \Doctrine\DBAL\Exception
	 */
	public function checkForSettingsRecordExist($pid): bool
	{
		$queryBuilder = DataBaseUtility::getQueryBuilderForTable(DataBaseUtility::SETTINGS_TABLE);
		$rows = $queryBuilder->select('*')
			->from(DataBaseUtility::SETTINGS_TABLE)
			->where(
				$queryBuilder->expr()->eq('type', $queryBuilder->createNamedParameter(self::TYPE)),
				$queryBuilder->expr()->eq('pid', $pid),
			)
			->executeQuery()->fetchAllAssociative();

		return count($rows) > 0;
	}

	protected function getNewsQueryBuilder()
	{
		return GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE);
	}
}
