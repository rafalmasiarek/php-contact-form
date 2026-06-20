<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

/**
 * Abstraction for resolving client IP and User-Agent from the current request.
 *
 * The default implementation reads from $_SERVER and is suitable for raw PHP
 * without reverse proxies. Behind a proxy (Nginx, Cloudflare, load balancer)
 * inject your own implementation that validates trusted proxy ranges before
 * trusting forwarded headers.
 *
 * @see \rafalmasiarek\ContactForm\Support\DefaultIpResolver
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
