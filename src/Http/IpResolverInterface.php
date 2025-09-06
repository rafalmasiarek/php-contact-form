<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Http;

/**
 * Abstraction for resolving request metadata (IP, User-Agent).
 *
 * The default implementation reads from $_SERVER,
 * but you can provide your own for CLI, queues, or frameworks.
 */
interface IpResolverInterface
{
    /**
     * Resolve the best-effort client IP address.
     *
     * @return string|null Client IP if available; otherwise null.
     */
    public function resolveClientIp(): ?string;

    /**
     * Resolve the User-Agent header.
     *
     * @return string|null User-Agent if available; otherwise null.
     */
    public function resolveUserAgent(): ?string;
}
