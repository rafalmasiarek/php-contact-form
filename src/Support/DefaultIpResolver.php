<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Support;

use rafalmasiarek\ContactForm\Contracts\IpResolverInterface;

/**
 * Default resolver that reads client IP and User-Agent directly from $_SERVER.
 *
 * Uses only REMOTE_ADDR — does not trust X-Forwarded-For or similar headers.
 * Suitable for raw PHP deployments without a reverse proxy in front.
 *
 * Behind a proxy (Nginx, Cloudflare, load balancer) inject your own implementation
 * that validates trusted proxy ranges before trusting forwarded headers.
 */
final class DefaultIpResolver implements IpResolverInterface
{
    /**
     * @var array<string, mixed>
     */
    private array $server;

    /**
     * @param array<string, mixed> $server Optional server params. Defaults to $_SERVER.
     */
    public function __construct(array $server = [])
    {
        $this->server = $server ?: (isset($_SERVER) && \is_array($_SERVER) ? $_SERVER : []);
    }

    /**
     * @inheritDoc
     */
    public function resolveClientIp(): ?string
    {
        $ip = $this->server['REMOTE_ADDR'] ?? null;

        return (\is_string($ip) && $ip !== '') ? $ip : null;
    }

    /**
     * @inheritDoc
     */
    public function resolveUserAgent(): ?string
    {
        $ua = $this->server['HTTP_USER_AGENT'] ?? null;

        return (\is_string($ua) && $ua !== '') ? $ua : null;
    }
}
