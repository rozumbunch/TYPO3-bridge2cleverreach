<?php

namespace Rozumbunch\Bridge2Cleverreach\Controller;

use Psr\Http\Message\ResponseInterface;
use Rozumbunch\Bridge2Cleverreach\Api\ApiManager;
use TYPO3\CMS\Core\Site\Entity\Site;
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

        if ($this->request->hasArgument('cleverreachapi')) {
            $requestData = $this->request->getArgument('cleverreachapi');
            $name = $requestData['newsletter-form-name'] ?? '';
            $surname = $requestData['newsletter-form-surname'] ?? '';
            $email = $requestData['newsletter-form-email'] ?? '';
            $settings = $this->getMergedSettings();

            if (empty($email)) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.noData', 'bridge2cleverreach')];
            } elseif (empty($settings['clientId']) || empty($settings['clientSecret']) || empty($settings['doubleOptInMailId']) || empty($settings['creverreachGroup'])) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.configuration', 'bridge2cleverreach')];
            } else {
                $access = $this->apiManager->authorize($settings['clientId'], $settings['clientSecret']);
                if ($access['error']) {
                    $response = ['success' => false, 'message' => $access['message']];
                } else {
                    $subscrib = $this->apiManager->createSubscriber($email, $settings['creverreachGroup'], false, [], ['firstname' => $name, 'lastname' => $surname], $access['access_token']);
                    if ($subscrib['error']) {
                        $response = ['success' => false, 'message' => $subscrib['message']];
                    } else {
                        $result = $this->apiManager->triggerDoubleOptInEmail($email, $settings['doubleOptInMailId'], ['firstname' => $name, 'lastname' => $surname], $access['access_token']);

                        if ($result['error']) {
                            $response = ['success' => false, 'message' => $result['message']];
                        } else {
                            $response = ['success' => true, 'message' => LocalizationUtility::translate('success.subscribe', 'bridge2cleverreach')];
                        }
                    }
                }
            }
        }

        $this->view->assign('response', $response);
        $this->view->assign('data', $this->getContentElementData());
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

        if ($this->request->hasArgument('cleverreachapi')) {
            $requestData = $this->request->getArgument('cleverreachapi');
            $email = $requestData['newsletter-form-email'] ?? '';
            $settings = $this->getMergedSettings();

            if (empty($email)) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.noData', 'bridge2cleverreach')];
            } elseif (empty($settings['clientId']) || empty($settings['clientSecret'])) {
                $response = ['success' => false, 'message' => LocalizationUtility::translate('error.configuration', 'bridge2cleverreach')];
            } else {
                $access = $this->apiManager->authorize($settings['clientId'], $settings['clientSecret']);
                if ($access['error']) {
                    $response = ['success' => false, 'message' => $access['message']];
                } else {
                    $result = $this->apiManager->deleteSubscriber($email, $settings['creverreachGroup'], $access['access_token']);
                    if ($result['error']) {
                        $response = ['success' => false, 'message' => $result['message']];
                    } else {
                        $response = ['success' => true, 'message' => LocalizationUtility::translate('success.unsubscribe', 'bridge2cleverreach')];
                    }
                }
            }
        }

        $this->view->assign('response', $response);
        $this->view->assign('data', $this->getContentElementData());
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
     * Get merged settings from TypoScript and site configuration
     *
     * @return array<string, mixed>
     */
    protected function getMergedSettings(): array
    {
        $settings = $this->settings;
        /** @var Site $site */
        $site = $this->request->getAttribute('site');
        $siteConfiguration = $site->getConfiguration();

        $settings['clientId'] = $siteConfiguration['cleverreachClientId'] ?? '';
        $settings['clientSecret'] = $siteConfiguration['cleverreachClientSecret'] ?? '';
        $settings['creverreachGroup'] = (isset($settings['creverreachGroup']) && $settings['creverreachGroup'])
            ? $settings['creverreachGroup']
            : $siteConfiguration['cleverreachGroup'];
        $settings['doubleOptInMailId'] = (isset($settings['doubleOptInMailId']) && $settings['doubleOptInMailId'])
            ? $settings['doubleOptInMailId']
            : $siteConfiguration['cleverreachDoubleOptInMailId'];

        return $settings;
    }
}
