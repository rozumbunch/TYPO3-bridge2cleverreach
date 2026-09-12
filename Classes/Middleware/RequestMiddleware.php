<?php

namespace Rozumbunch\Bridge2Cleverreach\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rozumbunch\Bridge2Cleverreach\Api\ApiManager;
use Rozumbunch\Bridge2Cleverreach\Utility\CleverReachGroupMapper;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Request Middleware for Cleverreach Headless API
 */
class RequestMiddleware implements MiddlewareInterface
{
    private const API_PATH = '/cleverreach/api';

    /** @var ApiManager */
    private $apiManager;

    public function __construct(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * Process the request
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();

        $normalizedPath = rtrim($path, '/');
        $normalizedApiPath = rtrim(self::API_PATH, '/');

        if ($normalizedPath !== $normalizedApiPath && strpos($normalizedPath, $normalizedApiPath . '/') !== 0) {
            return $handler->handle($request);
        }

        if ($request->getMethod() !== 'POST') {
            return new JsonResponse([
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.postOnly', 'bridge2cleverreach')
            ], 405);
        }

        $body = $request->getParsedBody();

        if (empty($body)) {
            $rawBody = $request->getBody()->getContents();
            if (!empty($rawBody)) {
                $jsonBody = json_decode($rawBody, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $body = $jsonBody;
                }
            }
        }

        $action = $body['action'] ?? '';

        $validationResult = $this->validateParameters($body, $action);
        if ($validationResult !== null) {
            return new JsonResponse($validationResult, 400);
        }

        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return new JsonResponse([
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.siteConfigNotFound', 'bridge2cleverreach')
            ], 500);
        }

        $settings = $this->getSettings($site);

        $configValidation = $this->validateConfiguration($settings, $action);
        if ($configValidation !== null) {
            return new JsonResponse($configValidation, 500);
        }

        try {
            $response = $this->processAction($action, $body, $settings);
            return new JsonResponse($response, $response['status'] ? 200 : 400);
        } catch (\Exception $e) {
            $message = LocalizationUtility::translate('api.error.general', 'bridge2cleverreach') ?? 'An error occurred: %s';
            return new JsonResponse([
                'status' => false,
                'message' => sprintf($message, $e->getMessage())
            ], 500);
        }
    }

    /**
     *
     * @param array<string, mixed> $body
     * @param string $action
     * @return array<string, mixed>|null Null wenn valide, sonst Fehler-Array
     */
    private function validateParameters(array $body, string $action): ?array
    {
        if (empty($action)) {
            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.actionRequired', 'bridge2cleverreach')
            ];
        }

        if (!in_array($action, ['subscribe', 'unsubscribe', 'get'], true)) {
            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.invalidAction', 'bridge2cleverreach')
            ];
        }

        $email = $body['email'] ?? '';
        if (empty($email)) {
            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.emailRequired', 'bridge2cleverreach')
            ];
        }


        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.invalidEmail', 'bridge2cleverreach')
            ];
        }

        if ($action === 'subscribe') {
            if (isset($body['name']) && !is_string($body['name'])) {
                return [
                    'status' => false,
                    'message' => LocalizationUtility::translate('api.error.nameMustBeString', 'bridge2cleverreach')
                ];
            }
            if (isset($body['surname']) && !is_string($body['surname'])) {
                return [
                    'status' => false,
                    'message' => LocalizationUtility::translate('api.error.surnameMustBeString', 'bridge2cleverreach')
                ];
            }
        }

        return null;
    }

    /**
     * Validiert die Konfiguration
     *
     * @param array<string, mixed> $settings
     * @param string $action
     * @return array<string, mixed>|null Null wenn valide, sonst Fehler-Array
     */
    private function validateConfiguration(array $settings, string $action): ?array
    {

        if (empty($settings['clientId']) || empty($settings['clientSecret'])) {
            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.configIncomplete', 'bridge2cleverreach')
            ];
        }

        if ($action === 'subscribe') {
            $hasMapping = !empty($settings['groups']);
            $hasDefaultGroup = !empty($settings['creverreachGroup']) && !empty($settings['doubleOptInMailId']);

            if (!$hasMapping && !$hasDefaultGroup) {
                return [
                    'status' => false,
                    'message' => LocalizationUtility::translate('api.error.configMappingOrDefault', 'bridge2cleverreach')
                ];
            }
        }

        if ($action === 'unsubscribe') {
            if (empty($settings['creverreachGroup'])) {
                return [
                    'status' => false,
                    'message' => LocalizationUtility::translate('api.error.configGroupRequired', 'bridge2cleverreach')
                ];
            }
        }

        return null;
    }

    /**
     * Verarbeitet die Action
     *
     * @param string $action
     * @param array<string, mixed> $body
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function processAction(string $action, array $body, array $settings): array
    {
        $email = $body['email'];
        $name = $body['name'] ?? '';
        $surname = $body['surname'] ?? '';

        $access = $this->apiManager->authorize($settings['clientId'], $settings['clientSecret']);
        if ($access['error']) {
            return [
                'status' => false,
                'message' => $access['message'] ?? LocalizationUtility::translate('api.error.authorizationFailed', 'bridge2cleverreach')
            ];
        }

        $token = $access['access_token'];

        if ($action === 'subscribe') {
            return $this->handleSubscribe($email, $name, $surname, $settings, $token, $body);
        } elseif ($action === 'unsubscribe') {
            return $this->handleUnsubscribe($email, $settings, $token);
        } else {
            $groupId = isset($body['groupId']) ? (int)$body['groupId'] : null;
            return $this->handleGetSubscriber($email, $groupId, $token);
        }
    }

    /**
     * Verarbeitet die Subscribe-Action
     *
     * @param string $email
     * @param string $name
     * @param string $surname
     * @param array<string, mixed> $settings
     * @param string $token
     * @param array<string, mixed> $body
     * @return array<string, mixed>
     */
    private function handleSubscribe(string $email, string $name, string $surname, array $settings, string $token, array $body): array
    {

        $newsletterParam = $body['newsletter'] ?? '';
        $newsletterGroups = $this->parseNewsletterParameter($newsletterParam);

        // Mapping aus den site-konfigurierten CleverReach-Gruppen laden
        $groupMapping = $settings['groups'] ?? [];

        // Wenn kein Mapping vorhanden, Fallback auf Standard-Gruppe
        if (empty($groupMapping) && !empty($settings['creverreachGroup'])) {
            $groupMapping = [
                'default' => [
                    'groupId' => (int)$settings['creverreachGroup'],
                    'formId' => (int)($settings['doubleOptInMailId'] ?? 0)
                ]
            ];
        }

        // Wenn keine Newsletter-Gruppen angegeben, Standard-Gruppe verwenden
        if (empty($newsletterGroups)) {
            if (isset($groupMapping['default'])) {
                $newsletterGroups = ['default'];
            } else {
                return [
                    'status' => false,
                    'message' => LocalizationUtility::translate('api.error.noNewsletterGroup', 'bridge2cleverreach')
                ];
            }
        }

        $errors = [];
        $successCount = 0;

        // Für jede Newsletter-Gruppe anmelden
        foreach ($newsletterGroups as $newsletterName) {
            // Mapping für diese Gruppe finden
            $mapping = $groupMapping[$newsletterName] ?? null;

            if (!$mapping || !isset($mapping['groupId']) || !isset($mapping['formId'])) {
                $message = LocalizationUtility::translate('api.error.groupConfigNotFound', 'bridge2cleverreach') ?? 'No configuration found for newsletter group \'%s\'';
                $errors[] = sprintf($message, $newsletterName);
                continue;
            }

            $groupId = (int)$mapping['groupId'];
            $formId = (string)$mapping['formId'];

            // Subscriber erstellen
            $subscribeResult = $this->apiManager->createSubscriber(
                $email,
                $groupId,
                false,
                [],
                ['firstname' => $name, 'lastname' => $surname],
                $token
            );

            if ($subscribeResult['error']) {
                $errorMessage = $subscribeResult['message'] ?? (LocalizationUtility::translate('api.error.unknownError', 'bridge2cleverreach') ?? 'Unknown error');
                $message = LocalizationUtility::translate('api.error.groupError', 'bridge2cleverreach') ?? 'Error in group \'%s\': %s';
                $errors[] = sprintf($message, $newsletterName, $errorMessage);
                continue;
            }

            // Double-Opt-In Email senden
            $doiResult = $this->apiManager->triggerDoubleOptInEmail(
                $email,
                $formId,
                ['firstname' => $name, 'lastname' => $surname],
                $token
            );

            if ($doiResult['error']) {
                $errorMessage = $doiResult['message'] ?? (LocalizationUtility::translate('api.error.unknownError', 'bridge2cleverreach') ?? 'Unknown error');
                $message = LocalizationUtility::translate('api.error.doiEmailFailed', 'bridge2cleverreach') ?? 'Error sending Double-Opt-In email for group \'%s\': %s';
                $errors[] = sprintf($message, $newsletterName, $errorMessage);
                continue;
            }

            $successCount++;
        }

        if ($successCount === 0) {
            $message = LocalizationUtility::translate('api.error.subscriptionFailed', 'bridge2cleverreach') ?? 'Subscription failed: %s';
            return [
                'status' => false,
                'message' => sprintf($message, implode(', ', $errors))
            ];
        }

        if (count($errors) > 0) {
            $message = LocalizationUtility::translate('api.success.subscriptionPartial', 'bridge2cleverreach') ?? 'Subscription partially successful (%d of %d groups). Errors: %s';
            return [
                'status' => true,
                'message' => sprintf($message, $successCount, count($newsletterGroups), implode(', ', $errors))
            ];
        }

        return [
            'status' => true,
            'message' => LocalizationUtility::translate('api.success.subscription', 'bridge2cleverreach')
        ];
    }

    /**
     * Verarbeitet die Unsubscribe-Action
     *
     * @param string $email
     * @param array<string, mixed> $settings
     * @param string $token
     * @return array<string, mixed>
     */
    private function handleUnsubscribe(string $email, array $settings, string $token): array
    {
        $result = $this->apiManager->deleteSubscriber(
            $email,
            (int)$settings['creverreachGroup'],
            $token
        );

        if ($result['error']) {
            return [
                'status' => false,
                'message' => $result['message'] ?? LocalizationUtility::translate('api.error.unsubscriptionFailed', 'bridge2cleverreach')
            ];
        }

        return [
            'status' => true,
            'message' => LocalizationUtility::translate('api.success.unsubscription', 'bridge2cleverreach')
        ];
    }

    /**
     * Verarbeitet die Get-Action (Subscriber-Status abfragen)
     *
     * @param string $email
     * @param int|null $groupId
     * @param string $token
     * @return array<string, mixed>
     */
    private function handleGetSubscriber(string $email, ?int $groupId, string $token): array
    {
        try {
            $response = $this->apiManager->getSubscriber($email, $token, $groupId);

            if ($response instanceof \Psr\Http\Message\ResponseInterface) {
                $statusCode = $response->getStatusCode();
                $body = json_decode($response->getBody()->getContents(), true);

                if ($statusCode === 200 && $body) {
                    return [
                        'status' => true,
                        'data' => $body,
                        'message' => LocalizationUtility::translate('api.success.subscriberFound', 'bridge2cleverreach')
                    ];
                } else {
                    return [
                        'status' => false,
                        'message' => LocalizationUtility::translate('api.error.subscriberNotFound', 'bridge2cleverreach')
                    ];
                }
            }

            return [
                'status' => false,
                'message' => LocalizationUtility::translate('api.error.unexpectedResponse', 'bridge2cleverreach')
            ];
        } catch (\Exception $e) {
            $message = LocalizationUtility::translate('api.error.getSubscriberFailed', 'bridge2cleverreach') ?? 'Error retrieving subscriber: %s';
            return [
                'status' => false,
                'message' => sprintf($message, $e->getMessage())
            ];
        }
    }

    /**
     * Retrieves the CleverReach credentials from the "CleverReach" tab on
     * the site configuration record (cleverreachClientId, ... columns from
     * Configuration/SiteConfiguration/Overrides/sites.php).
     *
     * @param Site $site
     * @return array<string, mixed>
     */
    private function getSettings(Site $site): array
    {
        $siteConfiguration = $site->getConfiguration();
        $settings = [
            'clientId' => (string)($siteConfiguration['cleverreachClientId'] ?? ''),
            'clientSecret' => (string)($siteConfiguration['cleverreachClientSecret'] ?? ''),
            'creverreachGroup' => (string)($siteConfiguration['cleverreachGroup'] ?? ''),
            'doubleOptInMailId' => (string)($siteConfiguration['cleverreachDoubleOptInMailId'] ?? ''),
        ];

        $settings['groups'] = $this->getGroupMapping($siteConfiguration);

        return $settings;
    }

    /**
     * @param array<string, mixed> $siteConfiguration
     * @return array<string, array{groupId: int, formId: string}>
     */
    private function getGroupMapping(array $siteConfiguration): array
    {
        return CleverReachGroupMapper::mapFromSiteConfiguration($siteConfiguration);
    }

    /**
     * Parst den Newsletter-Parameter und gibt eine Liste von Newsletter-Namen zurück
     *
     * @param string $newsletterParam
     * @return array<int, string>
     */
    private function parseNewsletterParameter(string $newsletterParam): array
    {
        if (empty($newsletterParam)) {
            return [];
        }

        $parts = GeneralUtility::trimExplode(',', $newsletterParam, true);
        $groups = [];

        foreach ($parts as $part) {
            $groupName = ltrim($part, '+');
            if (!empty($groupName)) {
                $groups[] = $groupName;
            }
        }

        return $groups;
    }
}
