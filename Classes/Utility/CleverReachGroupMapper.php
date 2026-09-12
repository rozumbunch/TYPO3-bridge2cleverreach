<?php

namespace Rozumbunch\Bridge2Cleverreach\Utility;

use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Reads the "cleverreachGroups" list from a site's raw configuration
 * (config.yaml / settings.yaml - there is no backend UI for this field,
 * see Configuration/SiteConfiguration/Overrides/sites.php) and turns it
 * into a lookup by group name:
 *
 *   cleverreachGroups:
 *     -
 *       name: Newsletter
 *       group: '123412341'
 *       doubleOptInMailId: '12341234'
 *
 * This is plain site configuration, unrelated to Site Sets, so it
 * resolves identically on TYPO3 v12, v13 and v14.
 */
final class CleverReachGroupMapper
{
    /**
     * @param array<string, mixed> $siteConfiguration
     * @return array<string, array{groupId: int, formId: string}>
     */
    public static function mapFromSiteConfiguration(array $siteConfiguration): array
    {
        $groups = [];
        foreach ($siteConfiguration['cleverreachGroups'] ?? [] as $group) {
            $name = trim((string)($group['name'] ?? ''));
            if ($name === '' || empty($group['group']) || empty($group['doubleOptInMailId'])) {
                continue;
            }
            $groups[$name] = [
                'groupId' => (int)$group['group'],
                'formId' => (string)$group['doubleOptInMailId'],
            ];
        }

        return $groups;
    }

    /**
     * Optional display name for the site's default list
     * (cleverreachGroupName in the site configuration). Empty when unset
     * so callers can fall back to the BE/FE language labels.
     */
    public static function getDefaultListName(Site $site): string
    {
        return trim((string)($site->getConfiguration()['cleverreachGroupName'] ?? ''));
    }
}
