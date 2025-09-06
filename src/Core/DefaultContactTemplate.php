<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Core;

use rafalmasiarek\ContactForm\Contracts\EmailTemplateInterface;
use rafalmasiarek\ContactForm\Model\ContactData;

/**
 * Default email template used when no custom template is provided.
 *
 * Rendering rules for extra fields:
 * - We render **only** from $d->body (user-visible extras). $d->meta is never rendered.
 *
 * Channel-aware values in $d->body:
 * - If a value is an array with channels:
 *     * ['html'] — non-empty string → render label then inject raw HTML block.
 *     * ['text'] — non-empty string → render label then print escaped text (<pre> in HTML; plain in TEXT).
 * - If a value is a scalar → render as a simple key/value pair with escaping.
 * - Any other shapes are skipped silently to avoid noisy output.
 */
final class DefaultContactTemplate implements EmailTemplateInterface
{
    /**
     * Render the HTML body for the contact message.
     *
     * @param ContactData $d Contact data to render (sanitized by the caller; meta is not used here).
     * @return string HTML content.
     */
    public function renderHtml(ContactData $d): string
    {
        $e = static function (string $s): string {
            return \htmlspecialchars($s, \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');
        };

        $lines = [
            '<h2 style="font-weight:bold;">CONTACT MESSAGE</h2>',
            '<p><strong>Name:</strong> ' . $e((string)$d->name) . '</p>',
            '<p><strong>Email:</strong> ' . $e((string)$d->email) . '</p>',
        ];

        if ($d->phone !== '') {
            $lines[] = '<p><strong>Phone:</strong> ' . $e((string)$d->phone) . '</p>';
        }

        // Additional user-visible details from BODY (not META)
        if (\is_array($d->body ?? null) && $d->body !== []) {
            foreach ($d->body as $key => $val) {
                $keyLabel = $e((string)$key);

                if (\is_array($val)) {
                    $html = isset($val['html']) && \is_string($val['html']) ? \trim($val['html']) : '';
                    $txt  = isset($val['text']) && \is_string($val['text']) ? \trim($val['text']) : '';

                    if ($html !== '') {
                        // Label + raw HTML block
                        $lines[] = '<p><strong>' . $keyLabel . ':</strong></p>';
                        $lines[] = $html; // trusted HTML produced by controlled hooks
                        continue;
                    }

                    if ($txt !== '') {
                        // Label + escaped plain text in <pre>
                        $lines[] = '<p><strong>' . $keyLabel . ':</strong></p>';
                        $lines[] = '<pre style="white-space:pre-wrap; margin:6px 0 10px 0;">' . $e($txt) . '</pre>';
                        continue;
                    }

                    // Unknown array shape → skip silently
                    continue;
                }

                if (\is_scalar($val) && (string)$val !== '') {
                    $lines[] = '<p><strong>' . $keyLabel . ':</strong> ' . $e((string)$val) . '</p>';
                }
            }
        }

        // Message body
        $lines[] = '<h3 style="font-weight:bold;">Message</h3>';
        $lines[] = '<pre style="white-space:pre-wrap;">' . $e((string)$d->message) . '</pre>';

        return \implode("\n", $lines);
    }

    /**
     * Render the plain-text alternative for the contact message.
     *
     * @param ContactData $d Contact data to render (sanitized by the caller; meta is not used here).
     * @return string|null Text content; null to omit the text part (we always return a string here).
     */
    public function renderText(ContactData $d): ?string
    {
        $out  = "CONTACT MESSAGE\n\n";
        $out .= "Name: " . (string)$d->name . "\n";
        $out .= "Email: " . (string)$d->email . "\n";

        if ($d->phone !== '') {
            $out .= "Phone: " . (string)$d->phone . "\n";
        }

        // Additional user-visible details from BODY (not META)
        if (\is_array($d->body ?? null) && $d->body !== []) {
            foreach ($d->body as $key => $val) {
                if (\is_array($val)) {
                    $txt  = isset($val['text']) && \is_string($val['text']) ? \trim($val['text']) : '';
                    $html = isset($val['html']) && \is_string($val['html']) ? \trim($val['html']) : '';

                    if ($txt !== '') {
                        $out .= (string)$key . ":\n" . $txt . "\n";
                        continue;
                    }
                    if ($html !== '') {
                        $out .= (string)$key . ": [see HTML part]\n";
                        continue;
                    }

                    // Unknown array shape → skip silently
                    continue;
                }

                if (\is_scalar($val) && (string)$val !== '') {
                    $out .= (string)$key . ': ' . (string)$val . "\n";
                }
            }
        }

        $out .= "\nMessage:\n" . (string)$d->message . "\n";
        return $out;
    }
}
