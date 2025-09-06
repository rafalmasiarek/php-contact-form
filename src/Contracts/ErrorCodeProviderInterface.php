<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

/**
 * Contracts for exceptions that carry a stable machine-readable error code.
 *
 * Implement this on your validators/hooks/transport exceptions so that the
 * ContactFormService can return consistent:
 *   ['ok' => false, 'code' => 'ERR_FOO', 'message' => '...']
 */
interface ErrorCodeProviderInterface
{
    /**
     * Return a stable symbolic code, e.g. "ERR_EMAIL_INVALID".
     */
    public function getErrorCode(): string;

    /**
     * Optional printf/vsprintf context passed to the message resolver.
     *
     * @return array<int|string, mixed> Positional or named context values.
     */
    public function getMessageContext(): array;
}
