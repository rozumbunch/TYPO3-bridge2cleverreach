<?php

namespace Rozumbunch\Bridge2Cleverreach\Backend\Form;

use Rozumbunch\Bridge2Cleverreach\Utility\CleverReachGroupMapper;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Backs the "settings.newsletterGroups" field of the Subscribeform and
 * Unsubscribeform FlexForms: populates the selectMultipleSideBySide items
 * from the site's configured CleverReach groups, and hides the field when
 * no additional groups are configured (only the default group from the
 * site's "CleverReach" tab is then used).
 */
class NewsletterGroupsFieldConfiguration
{
    public function __construct(private readonly SiteFinder $siteFinder)
    {
    }

    /**
     * itemsProcFunc for settings.newsletterGroups.
     *
     * @param array<string, mixed> $params
     */
    public function itemsProcFunc(array &$params): void
    {
        $pageId = (int)($params['effectivePid'] ?? $params['row']['pid'] ?? 0);
        if ($pageId <= 0) {
            return;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            return;
        }

        $groupNames = array_keys(CleverReachGroupMapper::mapFromSiteConfiguration($site->getConfiguration()));
        if ($groupNames === []) {
            return;
        }

        $defaultLabel = CleverReachGroupMapper::getDefaultListName($site);
        $params['items'][] = [
            'label' => $defaultLabel !== ''
                ? $defaultLabel
                : 'LLL:EXT:bridge2cleverreach/Resources/Private/Language/locallang_be.xlf:flexform.newsletterGroups.default',
            'value' => 'default',
        ];
        foreach ($groupNames as $groupName) {
            $params['items'][] = [
                'label' => $groupName,
                'value' => $groupName,
            ];
        }
    }

    /**
     * displayCond USER function for settings.newsletterGroups: only show
     * the field when the site has additional CleverReach groups configured.
     *
     * @param array<string, mixed> $parameter
     */
    public function isVisible(array $parameter): bool
    {
        $pageId = (int)($parameter['record']['pid'] ?? 0);

        return $this->getConfiguredGroupNames($pageId) !== [];
    }

    /**
     * @return string[]
     */
    private function getConfiguredGroupNames(int $pageId): array
    {
        if ($pageId <= 0) {
            return [];
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (SiteNotFoundException) {
            return [];
        }

        return array_keys(CleverReachGroupMapper::mapFromSiteConfiguration($site->getConfiguration()));
    }
}
