<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Model;

use rafalmasiarek\ContactForm\Model\ContactData;
use rafalmasiarek\ContactForm\Contracts\EmailTemplateInterface;
use rafalmasiarek\ContactForm\Core\DefaultContactTemplate;

/**
 * Value object representing an outbound email ready to be sent.
 *
 * Instances are typically built via {@see OutboundEmail::fromContactData()},
 * which uses an {@see EmailTemplateInterface} to render bodies and maps basic address
 * fields from SMTP config and ContactData.
 *
 * IMPORTANT:
 * - Templates receive a **sanitized ContactData** (top-level fields + `body` only).
 * - The internal `meta` bag from {@see ContactData} is **never** passed to templates
 *   or rendered into the message.
 */
final class OutboundEmail
{
    /**
     * @param string $to            Recipient email address.
     * @param string $from          Sender email address (envelope/from header).
     * @param string $replyTo       Reply-To email address.
     * @param string $subject       Message subject.
     * @param string $htmlBody      HTML body content.
     * @param string|null $textBody Optional text body; null means "use fallback".
     * @param list<array{path:string,name?:string}> $attachments Files to attach.
     */
    public function __construct(
        public string $to,
        public string $from,
        public string $replyTo,
        public string $subject,
        public string $htmlBody,
        public ?string $textBody = null,
        public array $attachments = []
    ) {}

    /**
     * Factory that builds a ready-to-send email from ContactData using a template.
     *
     * Address resolution:
     *  - to:       $smtp['to'] if present, otherwise $d->meta['deliver_to'] if present, otherwise empty string
     *  - from:     $smtp['from'] if present, otherwise $d->email
     *  - reply-to: $d->email if present, otherwise same as "from"
     *
     * Templating:
     *  - The template receives a **sanitized ContactData** with `meta` cleared.
     *  - `body` remains available for user-visible extras.
     *
     * @param ContactData                         $d            Contact form data.
     * @param array{to?:string,from?:string}      $smtp         Minimal SMTP mapping (only 'to' and 'from' are read here).
     * @param EmailTemplateInterface|null                  $tpl          Template to render bodies; default template if null.
     * @param list<array{path:string,name?:string}> $attachments Optional attachments.
     * @return self
     */
    public static function fromContactData(
        ContactData $d,
        array $smtp,
        ?EmailTemplateInterface $tpl = null,
        array $attachments = []
    ): self {
        $tpl = $tpl ?? new DefaultContactTemplate();

        // Build a sanitized copy for templates: preserve top-level fields + body, clear meta.
        $safe = new ContactData(
            (string)($d->name ?? ''),
            (string)($d->email ?? ''),
            (string)($d->message ?? ''),
            (string)($d->subject ?? ''),
            (string)($d->phone ?? ''),
            \is_array($d->body ?? null) ? $d->body : [],
            [] // META CLEARED — templates must not see internal meta
        );

        $subject = $safe->subject !== '' ? $safe->subject : 'New contact message';

        // Render using sanitized ContactData (type matches EmailTemplateInterface signature).
        $html = $tpl->renderHtml($safe);
        $text = $tpl->renderText($safe);
        if ($text === null || $text === '') {
            $text = strip_tags($html);
        }

        // Resolve addresses (may read routing hints from ORIGINAL meta, but never pass meta to templates).
        $to      = (string)($smtp['to']   ?? ($d->meta['deliver_to'] ?? ''));
        $from    = (string)($smtp['from'] ?? $safe->email);
        $replyTo = $safe->email !== '' ? $safe->email : $from;

        // Return using positional args for older PHP compatibility (no named args).
        return new self(
            $to,
            $from,
            $replyTo,
            $subject,
            $html,
            $text,
            $attachments
        );
    }
}
