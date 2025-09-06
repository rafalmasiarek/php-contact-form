<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Http;


use rafalmasiarek\ContactForm\Http\IpResolverInterface;
/**
 * Default resolver that reads client IP and User-Agent from $_SERVER.
 *
 * It honors the first entry of the X-Forwarded-For header if present,
 * otherwise falls back to REMOTE_ADDR.
 */
final class DefaultIpResolver implements IpResolverInterface
{
    /** @var array<string,mixed> */
    private array $server;

    /**
     * @param array<string,mixed> $server Optional server params map. If omitted, falls back to $_SERVER.
     */
    public function __construct(array $server = [])
    {
        $this->server = $server ?: (isset($_SERVER) && is_array($_SERVER) ? $_SERVER : []);
    }

    /**
     * @inheritDoc
     */
    public function resolveClientIp(): ?string
    {
        $fwd = $this->server['HTTP_X_FORWARDED_FOR'] ?? null ?? '';
        if ($fwd) {
            $parts = array_map('trim', explode(',', $fwd));
            return $parts[0] ?: null;
        }
        return $this->server['REMOTE_ADDR'] ?? null ?? null;
    }

    /**
     * @inheritDoc
     */
    public function resolveUserAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null ?? null;
    }
}
