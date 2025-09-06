<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

use rafalmasiarek\ContactForm\Model\OutboundEmail;

/**
 * Contract for sending outbound emails.
 *
 * Implementations may use any transport (SMTP, API, sendmail, etc.).
 */
interface EmailSenderInterface
{
    /**
     * Send an email message.
     *
     * Implementations should throw on transport/config errors.
     *
     * @param OutboundEmail $email The message to send.
     * @return bool True on success; false otherwise.
     *
     * @throws \Throwable On transport or configuration failure.
     */
    public function send(OutboundEmail $email): bool;
}
