<?php

namespace Rozumbunch\Bridge2Cleverreach\Controller;

use Psr\Http\Message\ResponseInterface;
use Rozumbunch\Bridge2Cleverreach\Api\ApiManager;
use Rozumbunch\Bridge2Cleverreach\Utility\CleverReachGroupMapper;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Cleverreach Controller.
 */
class CleverreachController extends ActionController
{
    /** @var ApiManager */
    private $apiManager;

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * Subscribe action
     *
     * @return ResponseInterface
     */
    public function subscribeAction(): ResponseInterface
    {
        $response = null;

        $siteCredentials = $this->getSiteCredentials();
        $selectableGroups = $this->resolveSelectableGroups($siteCredentials);

        if ($this->request->hasArgument('cleverreachapi')) {
            $requestData = $this->request->getArgument('cleverreachapi');
            $name = $requestData['newsletter-form-name'] ?? '';
            $surname = $requestData['newsletter-form-surname'] ?? '';
            $email = $requestData['newsletter-form-email'] ?? '';
            $groups = $this->resolveTargetGroups($selectableGroups, (string)($requestData['newsletter-group'] ?? ''));

            if (empty($email)) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.noData', 'bridge2cleverreach')];
            } elseif (empty($siteCredentials['clientId']) || empty($siteCredentials['clientSecret']) || $groups === []) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.configuration', 'bridge2cleverreach')];
            } else {
                $access = $this->apiManager->authorize($siteCredentials['clientId'], $siteCredentials['clientSecret']);
                if ($access['error']) {
                    $response = ['success' => false, 'message' => $access['message']];
                } else {
                    $response = $this->subscribeToGroups(
                        $email,
                        $name,
                        $surname,
                        $groups,
                        $access['access_token']
                    );
                }
            }
        }

        $this->view->assign('response', $response);
        $this->view->assign('data', $this->getContentElementData());
        $this->view->assign('newsletterGroupOptions', array_map(static fn(array $group): string => $group['label'], $selectableGroups));
        return $this->htmlResponse();
    }

    /**
     * Unsubscribe action
     *
     * @return ResponseInterface
     */
    public function unsubscribeAction(): ResponseInterface
    {
        $response = null;

        $siteCredentials = $this->getSiteCredentials();
        $selectableGroups = $this->resolveUnsubscribeGroups($siteCredentials);

        if ($this->request->hasArgument('cleverreachapi')) {
            $requestData = $this->request->getArgument('cleverreachapi');
            $email = $requestData['newsletter-form-email'] ?? '';
            $groups = $this->resolveTargetGroups($selectableGroups, (string)($requestData['newsletter-group'] ?? ''));

            if (empty($email)) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.noData', 'bridge2cleverreach')];
            } elseif (empty($siteCredentials['clientId']) || empty($siteCredentials['clientSecret']) || $groups === []) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.configuration', 'bridge2cleverreach')];
            } else {
                $access = $this->apiManager->authorize($siteCredentials['clientId'], $siteCredentials['clientSecret']);
                if ($access['error']) {
                    $response = ['success' => false, 'message' => $access['message']];
                } else {
                    $response = $this->unsubscribeFromGroups($email, $groups, $access['access_token']);
                }
            }
        }

        $this->view->assign('response', $response);
        $this->view->assign('data', $this->getContentElementData());
        $this->view->assign('newsletterGroupOptions', array_map(static fn(array $group): string => $group['label'], $selectableGroups));
        return $this->htmlResponse();
    }

    /**
     * Get content element data
     *
     * @return array<string, mixed>
     */
    protected function getContentElementData(): array
    {
        $currentContentObject = $this->request->getAttribute('currentContentObject');
        if ($currentContentObject instanceof ContentObjectRenderer) {
            return $currentContentObject->data;
        }

        return [];
    }


    /**
     * API credentials and default list from the site configuration tab.
     *
     * @return array{clientId: string, clientSecret: string, creverreachGroup: string, doubleOptInMailId: string}
     */
    protected function getSiteCredentials(): array
    {
        $site = $this->request->getAttribute('site');
        if (!$site instanceof Site) {
            return [
                'clientId' => '',
                'clientSecret' => '',
                'creverreachGroup' => '',
                'doubleOptInMailId' => '',
            ];
        }

        $configuration = $site->getConfiguration();

        return [
            'clientId' => (string)($configuration['cleverreachClientId'] ?? ''),
            'clientSecret' => (string)($configuration['cleverreachClientSecret'] ?? ''),
            'creverreachGroup' => (string)($configuration['cleverreachGroup'] ?? ''),
            'doubleOptInMailId' => (string)($configuration['cleverreachDoubleOptInMailId'] ?? ''),
        ];
    }

    protected function getDefaultGroupLabel(): string
    {
        $site = $this->request->getAttribute('site');
        if ($site instanceof Site) {
            $name = CleverReachGroupMapper::getDefaultListName($site);
            if ($name !== '') {
                return $name;
            }
        }

        return LocalizationUtility::translate('form.newsletterGroups.default', 'bridge2cleverreach') ?? 'Newsletter';
    }

    /**
     * @param array{clientId: string, clientSecret: string, creverreachGroup: string, doubleOptInMailId: string} $siteCredentials
     * @return array<string, array{label: string, groupId: int, formId: string}>
     */
    protected function resolveSelectableGroups(array $siteCredentials): array
    {
        $defaultGroup = ($siteCredentials['creverreachGroup'] !== '' && $siteCredentials['doubleOptInMailId'] !== '')
            ? [
                'label' => $this->getDefaultGroupLabel(),
                'groupId' => (int)$siteCredentials['creverreachGroup'],
                'formId' => $siteCredentials['doubleOptInMailId'],
            ]
            : null;

        $selectedNames = GeneralUtility::trimExplode(',', (string)($this->settings['newsletterGroups'] ?? ''), true);
        if ($selectedNames === []) {
            return $defaultGroup !== null ? ['default' => $defaultGroup] : [];
        }

        $site = $this->request->getAttribute('site');
        $namedGroups = $site instanceof Site ? CleverReachGroupMapper::mapFromSiteConfiguration($site->getConfiguration()) : [];

        $groups = [];
        foreach ($selectedNames as $groupName) {
            if ($groupName === 'default') {
                if ($defaultGroup !== null) {
                    $groups['default'] = $defaultGroup;
                }
                continue;
            }
            if (isset($namedGroups[$groupName])) {
                $groups[$groupName] = $namedGroups[$groupName] + ['label' => $groupName];
            }
        }

        return $groups;
    }

    /**
     * Groups this unsubscribe plugin offers.
     * Selected FlexForm groups, or every configured group when none are selected.
     *
     * @param array{clientId: string, clientSecret: string, creverreachGroup: string, doubleOptInMailId: string} $siteCredentials
     * @return array<string, array{label: string, groupId: int, formId: string}>
     */
    protected function resolveUnsubscribeGroups(array $siteCredentials): array
    {
        $groups = [];

        if ($siteCredentials['creverreachGroup'] !== '') {
            $groups['default'] = [
                'label' => $this->getDefaultGroupLabel(),
                'groupId' => (int)$siteCredentials['creverreachGroup'],
                'formId' => $siteCredentials['doubleOptInMailId'],
            ];
        }

        $site = $this->request->getAttribute('site');
        if ($site instanceof Site) {
            foreach (CleverReachGroupMapper::mapFromSiteConfiguration($site->getConfiguration()) as $name => $group) {
                $groups[$name] = $group + ['label' => $name];
            }
        }

        $selectedNames = GeneralUtility::trimExplode(',', (string)($this->settings['newsletterGroups'] ?? ''), true);
        if ($selectedNames !== []) {
            $groups = array_intersect_key($groups, array_flip($selectedNames));
        }

        return $groups;
    }

    /**
     * Groups the visitor should be subscribed to or unsubscribed from.
     *
     * - One selectable group: that group is used (no frontend select).
     * - Select value "all": every group configured on this plugin instance.
     * - Otherwise: the single group picked in the frontend select.
     *
     * @param array<string, array{label: string, groupId: int, formId: string}> $selectableGroups
     * @return array<string, array{label: string, groupId: int, formId: string}>
     */
    protected function resolveTargetGroups(array $selectableGroups, string $selectedKey): array
    {
        if ($selectableGroups === []) {
            return [];
        }

        if (count($selectableGroups) === 1) {
            return $selectableGroups;
        }

        if ($selectedKey === 'all' || $selectedKey === '') {
            return $selectableGroups;
        }

        if (isset($selectableGroups[$selectedKey])) {
            return [$selectedKey => $selectableGroups[$selectedKey]];
        }

        return [];
    }

    /**
     * @param array<string, array{label: string, groupId: int, formId: string}> $groups
     * @return array{success: bool, message: string}
     */
    private function subscribeToGroups(
        string $email,
        string $name,
        string $surname,
        array $groups,
        string $token
    ): array {
        $errors = [];
        $successCount = 0;

        foreach ($groups as $groupKey => $group) {
            $subscribeResult = $this->apiManager->createSubscriber(
                $email,
                $group['groupId'],
                false,
                [],
                ['firstname' => $name, 'lastname' => $surname],
                $token
            );
            if ($subscribeResult['error']) {
                $errors[] = $this->formatGroupError($groupKey, $group, $subscribeResult['message'] ?? '');
                continue;
            }

            $doiResult = $this->apiManager->triggerDoubleOptInEmail(
                $email,
                $group['formId'],
                ['firstname' => $name, 'lastname' => $surname],
                $token
            );
            if ($doiResult['error']) {
                $errors[] = $this->formatGroupError($groupKey, $group, $doiResult['message'] ?? '');
                continue;
            }

            $successCount++;
        }

        if ($successCount === 0) {
            $message = LocalizationUtility::translate('api.error.subscriptionFailed', 'bridge2cleverreach') ?? 'Subscription failed: %s';

            return [
                'success' => false,
                'message' => sprintf($message, implode(', ', $errors)),
            ];
        }

        if ($errors !== []) {
            $message = LocalizationUtility::translate('api.success.subscriptionPartial', 'bridge2cleverreach') ?? 'Subscription partially successful (%d of %d groups). Errors: %s';

            return [
                'success' => true,
                'message' => sprintf($message, $successCount, count($groups), implode(', ', $errors)),
            ];
        }

        return [
            'success' => true,
            'message' => LocalizationUtility::translate('success.subscribe', 'bridge2cleverreach') ?? '',
        ];
    }

    /**
     * @param array<string, array{label: string, groupId: int, formId: string}> $groups
     * @return array{success: bool, message: string}
     */
    private function unsubscribeFromGroups(string $email, array $groups, string $token): array
    {
        $errors = [];
        $successCount = 0;

        foreach ($groups as $groupKey => $group) {
            $result = $this->apiManager->deleteSubscriber($email, $group['groupId'], $token);
            if ($result['error']) {
                $errors[] = $this->formatGroupError($groupKey, $group, $result['message'] ?? '');
                continue;
            }
            $successCount++;
        }

        if ($successCount === 0) {
            return [
                'success' => false,
                'message' => LocalizationUtility::translate('api.error.unsubscriptionFailed', 'bridge2cleverreach') ?? '',
            ];
        }

        return [
            'success' => true,
            'message' => LocalizationUtility::translate('success.unsubscribe', 'bridge2cleverreach') ?? '',
        ];
    }

    /**
     * @param array{label: string, groupId: int, formId: string} $group
     */
    private function formatGroupError(string $groupKey, array $group, string $errorMessage): string
    {
        $label = $group['label'] !== '' ? $group['label'] : $groupKey;
        $message = LocalizationUtility::translate('api.error.groupError', 'bridge2cleverreach') ?? 'Error in group \'%s\': %s';

        return sprintf($message, $label, $errorMessage !== '' ? $errorMessage : (LocalizationUtility::translate('api.error.unknownError', 'bridge2cleverreach') ?? 'Unknown error'));
    }
}
