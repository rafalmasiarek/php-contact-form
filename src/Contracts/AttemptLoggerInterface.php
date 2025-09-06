<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

/**
 * Minimal logging contract used by the service.
 *
 * You can adapt a PSR-3 logger by implementing this interface.
 */
interface AttemptLoggerInterface
{
    /**
     * Log an informational message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function info(string $message, array $context = []): void;

    /**
     * Log a warning message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function warning(string $message, array $context = []): void;

    /**
     * Log an error message.
     *
     * @param string $message
     * @param array<string,mixed> $context
     */
    public function error(string $message, array $context = []): void;
}
