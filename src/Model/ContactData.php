<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Model;

/**
 * Mutable DTO carrying contact form payload through the pipeline.
 *
 * Semantics:
 * - Top-level properties (name, email, subject, message, phone) are common fields.
 * - body: user-visible, safe-to-expose fields that may be rendered into the email.
 * - meta: internal/diagnostic fields (CSRF, attemptId, ip, ua, flags, scan results, etc.).
 *
 * Templates should only render from top-level properties and `body`.
 * `meta` MUST be considered internal and never rendered directly.
 *
 * All properties are intentionally public for simplicity in hooks and templates.
 *
 * @psalm-type BodyArray = array<string, scalar|array|object|null>
 * @psalm-type MetaArray = array<string, mixed>
 */
final class ContactData
{
    /** @var string */
    public string $name;
    /** @var string */
    public string $email;
    /** @var string */
    public string $subject;
    /** @var string */
    public string $message;
    /** @var string */
    public string $phone;

    /**
     * User-visible, safe-to-expose fields to be included in the email.
     * Example: company, topic, orderId, preferredTime, etc.
     *
     * @var array<string, scalar|array|object|null>
     */
    public array $body;

    /**
     * Internal fields used by the service, hooks, and validators.
     * Example: csrf_token, attemptId, refId, ip, ua, threatScan, flags, attachments, etc.
     *
     * @var array<string, mixed>
     */
    public array $meta;

    /**
     * @param string $name
     * @param string $email
     * @param string $message
     * @param string $subject
     * @param string $phone
     * @param array<string, scalar|array|object|null> $body
     * @param array<string, mixed> $meta
     */
    public function __construct(
        string $name = '',
        string $email = '',
        string $message = '',
        string $subject = '',
        string $phone = '',
        array $body = [],
        array $meta = []
    ) {
        $this->name    = $name;
        $this->email   = $email;
        $this->message = $message;
        $this->subject = $subject;
        $this->phone   = $phone;
        $this->body    = $body;
        $this->meta    = $meta;
    }
}
