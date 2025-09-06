<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Mail;

use rafalmasiarek\ContactForm\Contracts\EmailSenderInterface;
use rafalmasiarek\ContactForm\Model\OutboundEmail;

/**
 * Class NativeMailSender
 *
 * Lightweight email sender using PHP's native {@see mail()} function.
 *
 * When to use:
 * - You have a working sendmail-compatible binary on the system
 *   (e.g., sendmail, msmtp, mhsendmail) and PHP is configured to use it.
 * - Simple messages without attachments are sufficient.
 *
 * Limitations:
 * - No attachment support (PHP's mail() requires manual MIME boundaries).
 * - Requires proper sendmail_path in php.ini or container setup.
 *
 * Docker + MailHog tip:
 * - Install msmtp in your PHP image and set:
 *     sendmail_path = "/usr/bin/msmtp -t"
 * - Point msmtp to MailHog (host: mailhog, port: 1025).
 *
 * Example:
 * $sender = new NativeMailSender(
 *     from: 'no-reply@example.test',
 *     fromName: 'ContactForm Demo',
 *     to: 'inbox@example.test',
 *     replyTo: null
 * );
 * $messageId = $sender->send($outboundEmail);
 */
final class NativeMailSender implements EmailSenderInterface
{
    /**
     * @param string      $from     Envelope/sender email, used in "From" header.
     * @param string      $fromName Human-readable sender name (RFC 2047 encoded).
     * @param string      $to       Primary recipient (comma-separated list supported by mail()).
     * @param string|null $replyTo  Optional Reply-To address (e.g., user email from a contact form).
     */
    public function __construct(
        private string $from,
        private string $fromName,
        private string $to,
        private ?string $replyTo = null
    ) {}

    /**
     * Send the email using PHP's native mail().
     *
     * Pulls subject and body from OutboundEmail via getters if available,
     * otherwise falls back to public properties (subject, bodyHtml, bodyText, body).
     *
     * @param  OutboundEmail $email
     * @return bool                  True on success, false otherwise.
     *
     * @throws \RuntimeException If mail() returns false (misconfiguration or transport failure).
     */
    public function send(OutboundEmail $email): bool
    {
        $subject  = $email->subject;
        $bodyHtml = $email->htmlBody;
        $bodyText = $email->textBody;
        $isHtml   = $bodyHtml !== null && $bodyHtml !== '';
        $message  = $isHtml ? (string)$bodyHtml : (string)$bodyText;

        // Headers
        $headers = [];

        $fromHeader = $this->fromName !== ''
            ? sprintf('From: %s <%s>', $this->encodeHeader($this->fromName), $this->from)
            : sprintf('From: %s', $this->from);
        $headers[] = $fromHeader;

        if ($this->replyTo) {
            $headers[] = sprintf('Reply-To: %s', $this->replyTo);
        }

        $headers[] = 'MIME-Version: 1.0';
        $headers[] = $isHtml
            ? 'Content-Type: text/html; charset=UTF-8'
            : 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;

        $messageId = sprintf(
            '<%s@%s>',
            bin2hex(random_bytes(16)),
            $this->domainFromEmail($this->from) ?? 'localhost'
        );
        $headers[] = 'Message-ID: ' . $messageId;

        $encodedSubject = $this->encodeHeader($subject);

        $ok = \mail(
            $this->to,
            $encodedSubject,
            $message,
            implode("\r\n", $headers)
        );

        if (!$ok) {
            throw new \RuntimeException('mail() returned false. Check sendmail_path / SMTP setup.');
        }

        return true;
    }

    /**
     * Encode header value using RFC 2047 (UTF-8, Base64).
     *
     * @param  string $value
     * @return string Encoded header value safe for non-ASCII.
     */
    private function encodeHeader(string $value): string
    {
        return trim(mb_encode_mimeheader($value, 'UTF-8', 'B', "\r\n"));
    }

    /**
     * Extract domain part from an email address.
     *
     * @param  string      $email
     * @return string|null Domain part or null if not available.
     */
    private function domainFromEmail(string $email): ?string
    {
        $parts = explode('@', $email);
        return $parts[1] ?? null;
    }
}
