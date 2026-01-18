<?php

namespace Rozumbunch\Bridge2Cleverreach\Api;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class ApiManager implements ApiManagerInterface
{
    const API_ENDPOINT = 'https://rest.cleverreach.com';

    /** @var RequestFactory */
    protected $requestFactory;

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * Authorize your  by credentials
     * @param string $clientId
     * @param string $clientSecret
     * @return mixed
     */
    public function authorize(string $clientId, string $clientSecret)
    {
        $additionalOptions = [
            'auth' => [
                $clientId,
                $clientSecret,
            ],
            'json' => [
                'grant_type' => 'client_credentials',
            ],
        ];

        try {
            $response = $this->requestFactory->request(self::API_ENDPOINT . '/oauth/token.php', 'POST', $additionalOptions);
            $body = $this->getResponseBody($response);

            if ($body->access_token) {
                $data = [
                    'access_token' => $body->access_token,
                    'error' => false,
                ];
            } else {
                $data = [
                    'access_token' => $body->access_token,
                    'error' => true,
                    'message' => LocalizationUtility::translate('error.authorization', 'bridge2cleverreach')
                ];
            }
        } catch (\Exception $e) {
            $data = [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
        return $data;
    }

    /**
     * Creates a subscriber.
     *
     * @param string $email
     * @param int $groupId
     * @param bool $active
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $globalAttributes
     * @param string $token
     *
     * @return array<string, mixed>
     */
    public function createSubscriber(string $email, int $groupId, bool $active, array $attributes, array $globalAttributes, string $token)
    {
        $now = time();
        $additionalOptions = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
            'json' =>
                ['email' => $email,
                    'registered' => $now,
                    'activated' => $active ? $now : 0,
                    'attributes' => $attributes,
                    'global_attributes' => $globalAttributes
                ]
        ];
        try {
            $response = $this->requestFactory->request(self::API_ENDPOINT . '/v3/groups.json/' . $groupId . '/receivers', 'POST', $additionalOptions);
            $body = $this->getResponseBody($response);
            $data = [
                'error' => false,
            ];
        } catch (\Exception $e) {
            $data = [
                'error' => true,
                'message' => $this->getErrorMessage($e->getMessage())
            ];
        }
        return $data;
    }

    /**
     * Returns a subscriber.
     *
     * @param string $email
     * @param string $token
     * @param int|null $groupId
     *
     * @return mixed
     */
    public function getSubscriber(string $email, string $token, ?int $groupId = null)
    {
        $additionalOptions = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
        ];

        if ($groupId) {
            return $this->requestFactory->request(self::API_ENDPOINT . "/v3/groups.json/{$groupId}/receivers/{$email}", 'GET', $additionalOptions);
        }
        return $this->requestFactory->request(self::API_ENDPOINT . "/v3/receivers.json/{$email}", 'GET', $additionalOptions);
    }

    /**
     * Sets the active status of a subscriber.
     *
     * @param string $email
     * @param int $groupId
     * @param bool $active
     * @param string $token
     *
     * @return mixed
     */
    public function setSubscriberStatus(string $email, int $groupId, $active, string $token)
    {
        if ($active) {
            return $this->requestFactory->request(self::API_ENDPOINT . "/v3/groups.json/{$groupId}/receivers/{$email}/activate", 'PUT');
        }
        return $this->requestFactory->request(self::API_ENDPOINT . "/v3/groups.json/{$groupId}/receivers/{$email}/deactivate", 'PUT');
    }

    /**
     * Triggers the Double-Opt-In email for a subscriber.
     *
     * @param string $email
     * @param int $formId
     * @param array<string, mixed> $options
     * @param string $token
     *
     * @return array<string, mixed>
     */
    public function triggerDoubleOptInEmail(string $email, int $formId, array $options, string $token)
    {
        $additionalOptions = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],

            'json' => [
                'email' => $email,
                'doidata' => array_merge(
                    [
                        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        'referer' => $_SERVER['HTTP_REFERER'] ?? 'http://localhost',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'FakeAgent/2.0 (Ubuntu/Linux)',
                    ],
                    $options
                )
            ],

        ];

        try {
            $response = $this->requestFactory->request(self::API_ENDPOINT . "/v3/forms.json/{$formId}/send/activate", 'POST', $additionalOptions);
            $body = $this->getResponseBody($response);
            $data = [
                'error' => false,
            ];
        } catch (\Exception $e) {
            $data = [
                'error' => true,
                'message' => $this->getErrorMessage($e->getMessage())
            ];
        }
        return $data;
    }

    /**
     * Triggers the Double-Opt-Out email for a subscriber.
     *
     * @param string $email
     * @param int $formId
     * @param array<string, mixed> $options
     * @param string $token
     *
     * @return mixed
     */
    public function triggerDoubleOptOutEmail(string $email, int $formId, array $options, string $token)
    {
        $additionalOptions = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
            'json' => [
                'email' => $email,
                'doidata' => array_merge(
                    [
                        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                        'referer' => $_SERVER['HTTP_REFERER'] ?? 'http://localhost',
                        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'FakeAgent/2.0 (Ubuntu/Linux)',
                    ],
                    $options
                )
            ],
        ];
        return $this->requestFactory->request('POST', self::API_ENDPOINT . "/v3/forms.json/{$formId}/send/deactivate", $additionalOptions);
    }

    /**
     * Deletes a subscriber.
     *
     * @param string $email
     * @param int $groupId
     * @param string $token
     *
     * @return array<string, mixed>
     */
    public function deleteSubscriber(string $email, int $groupId, string $token)
    {
        $additionalOptions = [
            'headers' => [
                'Authorization' => "Bearer {$token}",
            ],
        ];

        try {
            $response = $this->requestFactory->request(self::API_ENDPOINT . "/v3/groups.json/{$groupId}/receivers/{$email}", 'DELETE', $additionalOptions);
            $body = $this->getResponseBody($response);
            $data = [
                'error' => false,
            ];
        } catch (\Exception $e) {
            $data = [
                'error' => true,
                'message' => $this->getErrorMessage($e->getMessage())
            ];
        }
        return $data;
    }

    /**
     * @param string $massage
     * @return string
     */
    private function getErrorMessage(string $massage): string
    {
        $jsonToArray = json_decode(mb_substr($massage, 10 + mb_strrpos($massage, 'response:')));
        if ($jsonToArray && isset($jsonToArray->error->message)) {
            return $jsonToArray->error->message;
        }
        return $massage;
    }

    /**
     * @param \Psr\Http\Message\ResponseInterface $result
     * @return mixed
     */
    private function getResponseBody(ResponseInterface $result)
    {
        if ($contents = $result->getBody()->getContents()) {
            return json_decode($contents);
        }
        return $contents;
    }
}
