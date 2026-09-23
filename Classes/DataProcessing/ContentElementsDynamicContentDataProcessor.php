<?php

namespace Rozumbunch\Bridge2Cleverreach\DataProcessing;

use Rozumbunch\Bridge2Cleverreach\Controller\CleverreachController;
use Rozumbunch\Bridge2Cleverreach\Domain\Repository\ContentElementsRepository;
use Rozumbunch\Bridge2Cleverreach\Services\DynamicContentService;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContentElementsDynamicContentDataProcessor
{
	public function getItems(array &$params, CleverreachController $obj) {

		$allowedCTypes = GeneralUtility::trimExplode(
			',',
			(string)(DynamicContentService::getDynamicContentConfig(ContentElementsRepository::TYPE)['allowedCTypes'] ?? ''),
			true
		);
		$contentElements = $obj->contentElementsRepository->getContentElementsForEndpoint(
			(int)$params['site']->getRootPageId(),
			$allowedCTypes
		);

		foreach ($contentElements as $contentElement) {
			$fileUrl = '';
			$firstFile = $this->getFirstFileReference((int)$contentElement['uid']);
			if ($firstFile) {
				$fileUrl = $params['site']->getBase() . ltrim($firstFile->getPublicUrl(), '/');
			}

			$params['items'][] = [
				'title' => $contentElement['header'],
				'teaser' => isset($contentElement['subheader']) ? $contentElement['subheader'] : '',
				'display_info' => $contentElement['header'],
				'description' => $contentElement['bodytext'] ?? '',
				'image' => $fileUrl,
				'url' => (string)$params['site']->getRouter()->generateUri((int)$contentElement['pid']),
			];
		}
	}

	/**
	 * @return \TYPO3\CMS\Core\Resource\FileReference|null
	 */
	protected function getFirstFileReference(int $contentElementUid)
	{
		foreach (['assets', 'image'] as $fieldName) {
			if (!isset($GLOBALS['TCA']['tt_content']['columns'][$fieldName])) {
				continue;
			}

			$fileReferences = GeneralUtility::makeInstance(FileRepository::class)->findByRelation('tt_content', $fieldName, $contentElementUid);
			if (!empty($fileReferences)) {
				return $fileReferences[0];
			}
		}

		return null;
	}
}
