<?php

namespace Rozumbunch\Bridge2Cleverreach\Api;

interface ApiManagerInterface
{
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
    public function createSubscriber(
        string $email,
        int $groupId,
        bool $active,
        array $attributes,
        array $globalAttributes,
        string $token
    );

    /**
     * Returns a subscriber.
     *
     * @param string $email
     * @param string $token
     * @param int|null $groupId
     *
     * @return mixed
     */
    public function getSubscriber(string $email, string $token, ?int $groupId = null);

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
    public function setSubscriberStatus(string $email, int $groupId, $active, string $token);

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
    public function triggerDoubleOptInEmail(string $email, int $formId, array $options, string $token);

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
    public function triggerDoubleOptOutEmail(string $email, int $formId, array $options, string $token);

    /**
     * Deletes a subscriber.
     *
     * @param string $email
     * @param int $groupId
     * @param string $token
     *
     * @return array<string, mixed>
     */
    public function deleteSubscriber(string $email, int $groupId, string $token);
}
