<?php

declare(strict_types=1);

namespace ContactForm\Hook;

use rafalmasiarek\ContactForm\Contracts\ContactFormHookInterface;
use rafalmasiarek\ContactForm\Core\ContactDataHook;

/**
 * Example hook that annotates sender's IP into ContactData meta.
 */
final class AnnotateIpHook implements ContactFormHookInterface
{
    /**
     * Add client IP into the DTO meta before validation runs.
     */
    public function onBeforeValidate(ContactDataHook $d): void
    {
        $data = $d->data();

        // Ensure containers exist
        if (!\is_array($data->meta ?? null)) {
            $data->meta = [];
        }
        if (!\is_array($data->body ?? null)) {
            $data->body = [];
        }
        // Simple resolver: X-Forwarded-For first, then REMOTE_ADDR
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

        if ($ip !== '') {
            // INTERNAL: store raw IP in meta (not rendered)
            $data->meta['ip'] = $ip;

            // EMAIL RENDERING: provide both HTML and text channels in body
            $href = 'https://ipinfo.io/' . rawurlencode($ip);
            $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $data->body['Ip'] = [
                'text' => $ip,
                'html' => '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $safeIp . '</a>',
            ];
        }
    }

    /**
     * No-op example.
     *
     * @param array<string,mixed> $validatorsMeta
     */
    public function onAfterValidate(ContactDataHook $d, array $validatorsMeta): void {}

    /**
     * No-op example.
     */
    public function onAfterSend(ContactDataHook $d, string $messageId = ''): void {}

    /**
     * No-op example.
     */
    public function onSendFailure(ContactDataHook $d, \Throwable $e): void {}
}
