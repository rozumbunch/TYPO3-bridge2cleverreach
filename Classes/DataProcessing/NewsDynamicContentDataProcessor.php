<?php

namespace Rozumbunch\Bridge2Cleverreach\DataProcessing;

use Rozumbunch\Bridge2Cleverreach\Controller\CleverreachController;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class NewsDynamicContentDataProcessor
{
	public function getItems(array &$params, CleverreachController $obj) {

		if (!ExtensionManagementUtility::isLoaded('news')) {
			return;
		}

		$news = $obj->newsRepository->getSelectedNewsUidsForApiCleverReach((int)$params['site']->getRootPageId());
		foreach ($news as $newsItem) {
			$fileUrl = '';
			$fileReferences = GeneralUtility::makeInstance(FileRepository::class)->findByRelation('tx_news_domain_model_news', 'fal_media', (int)$newsItem['uid']);
			$firstFile = $fileReferences[0] ?? null;
			if ($firstFile) {
				$fileUrl = $params['site']->getBase() . ltrim($firstFile->getPublicUrl(), '/');
			}

			$detailPid = $params['detailPid'];
			$params['items'][] = [
				'title' => $newsItem['title'],
				'teaser' => $newsItem['teaser'],
				'display_info' => $newsItem['title'],
				'description' => $newsItem['bodytext'],
				'image' => $fileUrl,
				'url' => $this->getNewsDetailUrl($detailPid, $newsItem['uid'], $params['site']),
			];
		}
	}

	/**
	 * @param $detailPid
	 * @param $newsUid
	 * @param Site $site
	 * @return string
	 */
	public function getNewsDetailUrl($detailPid, $newsUid, Site $site)
	{
		$router = $site->getRouter();

		$uri = $router->generateUri(
			$detailPid,
			[
				'tx_news_pi1' => [
					'controller' => 'News',
					'action' => 'detail',
					'news' => $newsUid,
				]
			]
		);
		return (string)$uri;
	}
}