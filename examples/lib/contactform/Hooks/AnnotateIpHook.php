k<?php

    declare(strict_types=1);

    namespace ContactForm\Hook;

    use rafalmasiarek\ContactForm\Contracts\ContactFormHookInterface;
    use rafalmasiarek\ContactForm\Core\ContactDataHook;

    /**
     * Request context hook (addon).
     *
     * Responsibilities (outside of mail classes):
     * - Resolve client IP with a trusted-proxy policy and store it in ContactData->meta['ip'].
     * - Optionally attach User-Agent (meta['ua']) and Referer (meta['referer']) if enabled.
     *
     * Configuration:
     *   new AnnotateIpHook([
     *     'trust'        => ['127.0.0.1', '::1', '10.0.0.0/8'], // proxies you trust; empty => ignore proxy headers
     *     'prioritize'   => ['forwarded', 'x-forwarded-for', 'cf-connecting-ip', 'true-client-ip', 'x-real-ip'],
     *     'allowPrivate' => true,   // allow RFC1918/reserved IPs in result
     *     'attachUA'     => true,   // also put meta['ua']
     *     'attachReferer'=> false,  // also put meta['referer']
     *   ]);
     */
    final class AnnotateIpHook implements ContactFormHookInterface
    {
        /** @var list<string> */
        private array $trustedCidrs;
        /** @var list<string> */
        private array $priority;
        private bool $allowPrivate;
        private bool $attachUA;
        private bool $attachReferer;

        /**
         * @param array{
         *   trust?: array<int,string>,
         *   prioritize?: array<int,string>,
         *   allowPrivate?: bool,
         *   attachUA?: bool,
         *   attachReferer?: bool
         * } $options
         */
        public function __construct(array $options = [])
        {
            $this->trustedCidrs   = array_values(array_filter($options['trust'] ?? [], 'is_string'));
            $this->priority       = array_values($options['prioritize'] ?? [
                'forwarded',
                'x-forwarded-for',
                'cf-connecting-ip',
                'true-client-ip',
                'x-real-ip',
            ]);
            $this->allowPrivate   = (bool)($options['allowPrivate'] ?? true);
            $this->attachUA       = (bool)($options['attachUA'] ?? true);
            $this->attachReferer  = (bool)($options['attachReferer'] ?? false);
        }

        public function onBeforeValidate(ContactDataHook $d): void
        {
            // Prefer kontekst z withRequestBody()/ctx(), ale mamy też fallback na $_SERVER
            $server = $_SERVER; // prosty fallback; możesz tu kiedyś zaczytać z $d->ctx('server')
            $ip = $this->resolveIp($server);

            if ($ip !== null) {
                $d->data()->meta['ip'] = $ip;
            }
            if ($this->attachUA && isset($server['HTTP_USER_AGENT']) && is_string($server['HTTP_USER_AGENT'])) {
                $d->data()->meta['ua'] = $server['HTTP_USER_AGENT'];
            }
            if ($this->attachReferer && isset($server['HTTP_REFERER']) && is_string($server['HTTP_REFERER'])) {
                $d->data()->meta['referer'] = $server['HTTP_REFERER'];
            }
        }

        /** @param array<string,mixed> $validatorsMeta */
        public function onAfterValidate(ContactDataHook $d, array $validatorsMeta): void {}
        public function onAfterSend(ContactDataHook $d, string $messageId = ''): void {}
        public function onSendFailure(ContactDataHook $d, \Throwable $e): void {}

        /**
         * Resolve IP using trusted-proxy policy (no PSR-7 dependency).
         *
         * @param array<string,mixed> $server
         */
        private function resolveIp(array $server): ?string
        {
            $remote = isset($server['REMOTE_ADDR']) && is_string($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : null;

            // If peer isn't trusted, ignore proxy headers entirely
            $trustedPeer = $remote !== null && $this->isTrusted($remote);
            if (!$trustedPeer) {
                return $this->filterIp($remote);
            }

            // Try headers by priority
            $headers = static function (string $key) use ($server): array {
                $h = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
                return isset($server[$h]) && is_string($server[$h]) ? [$server[$h]] : [];
            };

            foreach ($this->priority as $src) {
                $ip = null;
                if ($src === 'forwarded') {
                    $ip = $this->fromForwarded($headers('forwarded'));
                } elseif ($src === 'x-forwarded-for') {
                    $ip = $this->fromXForwardedFor($headers('x-forwarded-for'));
                } else {
                    $ip = $this->firstValid($headers($src));
                }
                if ($ip !== null) {
                    return $ip;
                }
            }

            return $this->filterIp($remote);
        }

        /** @param list<string> $values */
        private function fromForwarded(array $values): ?string
        {
            foreach ($values as $line) {
                foreach (array_map('trim', explode(',', $line)) as $elem) {
                    foreach (explode(';', $elem) as $pair) {
                        $pair = trim($pair);
                        if (stripos($pair, 'for=') !== 0) continue;
                        $val = trim(substr($pair, 4), " \t\"'");
                        if ($val !== '' && $val[0] === '[' && substr($val, -1) === ']') {
                            $val = substr($val, 1, -1);
                        }
                        $val = preg_replace('/:\d+$/', '', $val ?? '') ?? '';
                        $ip  = $this->filterIp($val);
                        if ($ip !== null) return $ip;
                    }
                }
            }
            return null;
        }

        /** @param list<string> $values */
        private function fromXForwardedFor(array $values): ?string
        {
            foreach ($values as $line) {
                foreach (array_map('trim', explode(',', $line)) as $cand) {
                    if ($cand !== '' && $cand[0] === '[' && substr($cand, -1) === ']') {
                        $cand = substr($cand, 1, -1);
                    }
                    $cand = preg_replace('/:\d+$/', '', $cand) ?? $cand;
                    $ip = $this->filterIp($cand);
                    if ($ip !== null) return $ip;
                }
            }
            return null;
        }

        /** @param list<string> $values */
        private function firstValid(array $values): ?string
        {
            foreach ($values as $v) {
                $ip = $this->filterIp($v);
                if ($ip !== null) return $ip;
            }
            return null;
        }

        private function filterIp(?string $ip): ?string
        {
            if (!is_string($ip) || $ip === '') return null;
            $ip = trim($ip, " \t\n\r\0\x0B\"'");
            if ($ip !== '' && $ip[0] === '[' && substr($ip, -1) === ']') {
                $ip = substr($ip, 1, -1);
            }

            $isV4 = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
            $isV6 = !$isV4 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;

            if (!$isV4 && !$isV6) return null;

            if (!$this->allowPrivate) {
                $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
                if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
                    return null;
                }
            }
            return $ip;
        }

        private function isTrusted(string $ip): bool
        {
            foreach ($this->trustedCidrs as $cidr) {
                if ($this->inCidr($ip, $cidr)) return true;
            }
            return false;
        }

        private function inCidr(string $ip, string $cidr): bool
        {
            if (strpos($cidr, '/') === false) return $ip === $cidr;
            [$subnet, $mask] = explode('/', $cidr, 2);
            $mask = (int)$mask;

            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false &&
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            ) {
                $ipL  = ip2long($ip);
                $netL = ip2long($subnet);
                $msk  = (-1 << (32 - $mask)) & 0xFFFFFFFF;
                return ($ipL & $msk) === ($netL & $msk);
            }

            if (
                filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false &&
                filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false
            ) {
                $ipB  = inet_pton($ip);
                $netB = inet_pton($subnet);
                if ($ipB === false || $netB === false) return false;
                $bytes = intdiv($mask, 8);
                $bits  = $mask % 8;
                if ($bytes > 0 && substr($ipB, 0, $bytes) !== substr($netB, 0, $bytes)) return false;
                if ($bits === 0) return true;
                $ipByte  = ord($ipB[$bytes]);
                $netByte = ord($netB[$bytes]);
                $maskByte = ~((1 << (8 - $bits)) - 1) & 0xFF;
                return ($ipByte & $maskByte) === ($netByte & $maskByte);
            }

            return false;
        }
    }
