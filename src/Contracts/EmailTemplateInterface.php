<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

use rafalmasiarek\ContactForm\Model\ContactData;

/**
 * Contract for rendering email bodies from ContactData.
 *
 * Implementations are responsible for producing the HTML and plain-text bodies
 * shown in outbound messages. They may freely use $d->meta to include
 * additional context (IP, UA, referer, country, etc.) previously attached
 * by hooks in the application layer.
 */
interface EmailTemplateInterface
{
    /**
     * Render the HTML body for an outbound email.
     *
     * @param ContactData $d Contact form data, including optional meta.
     * @return string HTML markup for the message body.
     */
    public function renderHtml(ContactData $d): string;

    /**
     * Render the plain-text body for an outbound email.
     *
     * Return null to allow the caller to auto-fallback to a text version
     * generated from the HTML body (e.g., via strip_tags()).
     *
     * @param ContactData $d Contact form data, including optional meta.
     * @return string|null Text body or null to let caller fallback.
     */
    public function renderText(ContactData $d): ?string;
}
