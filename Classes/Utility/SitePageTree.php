<?php

declare(strict_types=1);

namespace Rozumbunch\Bridge2Cleverreach\Utility;

use Doctrine\DBAL\ArrayParameterType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Page uids of one site: the root page and every non-deleted page below it.
 */
class SitePageTree
{
    /**
     * @return int[]
     */
    public static function collectPageIds(int $rootPageId): array
    {
        if ($rootPageId <= 0) {
            return [];
        }

        $pageIds = [$rootPageId];
        $parentIds = [$rootPageId];
        $depth = 0;

        while ($parentIds !== [] && $depth < 20) {
            $depth++;
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

            $childIds = array_map(
                'intval',
                $queryBuilder
                    ->select('uid')
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->in(
                            'pid',
                            $queryBuilder->createNamedParameter($parentIds, ArrayParameterType::INTEGER)
                        )
                    )
                    ->executeQuery()
                    ->fetchFirstColumn()
            );

            $parentIds = array_values(array_diff($childIds, $pageIds));
            foreach ($parentIds as $pageId) {
                $pageIds[] = $pageId;
            }
        }

        return $pageIds;
    }
}
