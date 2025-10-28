<?php

namespace Alegiac\ReleaseManager\Contracts;

/**
 * Notification Driver Interface
 * 
 * Contract for notification drivers to implement.
 * 
 * @package Alegiac\ReleaseManager\Contracts
 */
interface NotificationDriverInterface
{
    /**
     * Send notification message
     *
     * @param array $message
     * @return bool
     */
    public function send(array $message): bool;

    /**
     * Check if driver is properly configured
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Get driver name
     *
     * @return string
     */
    public function getName(): string;
}
