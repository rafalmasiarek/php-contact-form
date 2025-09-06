<?php

declare(strict_types=1);

namespace ContactForm\Validators;

use rafalmasiarek\ContactForm\Support\ArrayMessageResolver;
use rafalmasiarek\ContactForm\Support\ContactDataValidator;
use rafalmasiarek\ContactForm\Core\ValidationException;

/**
 * Required fields validator factory compatible with setValidators([...]).
 *
 * Usage:
 *   RequiredFieldsValidator::registerDefaultMessages($resolver);
 *   $svc->setValidators([
 *       'required' => RequiredFieldsValidator::requireFields(['name','email','message']),
 *   ]);
 */
final class FieldsValidator
{
    // --- Common, field-agnostic codes (used by email validator) ---
    public const EMAIL_INVALID = 'EMAIL_INVALID';
    public const EMAIL_NO_MX   = 'EMAIL_NO_MX';

    // --- Common, field-specific codes for default fields used by the form ---
    public const NAME_REQUIRED    = 'NAME_REQUIRED';
    public const EMAIL_REQUIRED   = 'EMAIL_REQUIRED';
    public const SUBJECT_REQUIRED = 'SUBJECT_REQUIRED';
    public const MESSAGE_REQUIRED = 'MESSAGE_REQUIRED';
    public const PHONE_REQUIRED   = 'PHONE_REQUIRED';

    public const SUBJECT_TOO_LONG = 'SUBJECT_TOO_LONG';
    public const MESSAGE_TOO_LONG = 'MESSAGE_TOO_LONG';
    public const PHONE_TOO_LONG   = 'PHONE_TOO_LONG';

    /**
     * Register default human-readable messages for common validation codes.
     *
     * Call this once when wiring your resolver (you may still override any texts per project/locale).
     *
     * @param ArrayMessageResolver $resolver Resolver to extend with default messages.
     * @return void
     */
    public static function registerDefaultMessages(ArrayMessageResolver $resolver): void
    {
        /** Register typical messages for common fields with numeric app codes. */
        $resolver->extend([
            // Requireds (pick the ones you actually use)
            'NAME_REQUIRED'    => ['message' => 'Name is required.',               'http' => 422],
            'EMAIL_REQUIRED'   => ['message' => 'Email is required.',              'http' => 422],
            'MESSAGE_REQUIRED' => ['message' => 'Message is required.',            'http' => 422],

            // Email specifics
            'EMAIL_INVALID'    => ['message' => 'Email address is invalid.',       'http' => 422],
            'EMAIL_NO_MX'      => ['message' => 'Email domain has no MX record.',  'http' => 422],

            // Length limits (examples)
            'SUBJECT_TOO_LONG' => ['message' => 'Subject is too long.',            'http' => 422],
            'MESSAGE_TOO_LONG' => ['message' => 'Message is too long.',            'http' => 422],
        ]);
    }

    /**
     * Require that certain fields are non-empty strings (after trim).
     *
     * Error code format: FIELD_REQUIRED (uppercased).
     * For common fields (name, email, subject, message, phone) we pre-register default messages.
     * For custom fields, register your own messages or let the resolver fall back to the code.
     *
     * @param string[] $fields Field names (built-ins: name,email,subject,message,phone; other names read from meta).
     * @return callable(ContactDataValidator): void
     */
    public static function required(array $fields): callable
    {
        return function (ContactDataValidator $d) use ($fields): void {
            foreach ($fields as $field) {
                $val = (string) self::value($d, $field);
                if (trim($val) === '') {
                    throw new ValidationException(strtoupper($field) . '_REQUIRED', $field, '');
                }
            }
        };
    }

    /**
     * Validate e-mail address.
     *
     * Modes:
     *  - 'loose'  => Uses FILTER_VALIDATE_EMAIL (permissive)
     *  - 'strict' => Stricter syntax rules + mandatory MX/A DNS check
     *
     * Error codes:
     *  - EMAIL_INVALID (or <FIELD>_INVALID if you pass a different field name)
     *  - EMAIL_NO_MX   (or <FIELD>_NO_MX) — strict mode only
     *
     * @param string               $field ContactData property/meta name (default: 'email')
     * @param 'loose'|'strict'     $mode  Validation mode
     * @return callable(ContactDataValidator): void
     */
    public static function email(string $field = 'email', string $mode = 'loose'): callable
    {
        return function (ContactDataValidator $d) use ($field, $mode): void {
            $val = (string) self::value($d, $field);

            if ($val === '') {
                // Leave "missing" detection to required(); this validator checks format only.
                return;
            }

            $prefix = strtoupper($field) . '_';
            $invalidCode = ($field === 'email') ? self::EMAIL_INVALID : ($prefix . 'INVALID');
            $noMxCode    = ($field === 'email') ? self::EMAIL_NO_MX   : ($prefix . 'NO_MX');

            if ($mode === 'loose') {
                if (!filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    throw new ValidationException($invalidCode, $field, '');
                }
                return;
            }

            // STRICT mode — syntax rules
            $atPos = strrpos($val, '@');
            if ($atPos === false) {
                throw new ValidationException($invalidCode, $field, '');
            }

            $local  = substr($val, 0, $atPos);
            $domain = substr($val, $atPos + 1);

            // Local-part: [A-Za-z0-9._%+-]+, no consecutive dots, no leading/trailing dot
            if (!preg_match('/^[A-Za-z0-9._%+\-]+$/', $local)) {
                throw new ValidationException($invalidCode, $field, '');
            }
            if ($local === '' || str_contains($local, '..') || $local[0] === '.' || substr($local, -1) === '.') {
                throw new ValidationException($invalidCode, $field, '');
            }

            // Domain: convert IDN to ASCII if possible
            $asciiDomain = $domain;
            if (function_exists('idn_to_ascii')) {
                $ascii = idn_to_ascii($domain, IDNA_DEFAULT);
                if (is_string($ascii) && $ascii !== '') {
                    $asciiDomain = $ascii;
                }
            }

            // Domain allowed chars, no consecutive dots
            if (!preg_match('/^[A-Za-z0-9.-]+$/', $asciiDomain) || str_contains($asciiDomain, '..')) {
                throw new ValidationException($invalidCode, $field, '');
            }

            // Split into labels and validate each
            $labels = explode('.', $asciiDomain);
            if (count($labels) < 2) { // require a TLD
                throw new ValidationException($invalidCode, $field, '');
            }
            foreach ($labels as $lab) {
                $len = strlen($lab);
                if ($len < 1 || $len > 63 || $lab[0] === '-' || substr($lab, -1) === '-') {
                    throw new ValidationException($invalidCode, $field, '');
                }
            }
            // TLD: alpha only, 2–63 chars
            $tld = end($labels);
            if (!preg_match('/^[A-Za-z]{2,63}$/', (string) $tld)) {
                throw new ValidationException($invalidCode, $field, '');
            }

            // Mandatory MX/A DNS check in strict mode
            if (!checkdnsrr($asciiDomain, 'MX') && !checkdnsrr($asciiDomain, 'A')) {
                throw new ValidationException($noMxCode, $field, '');
            }
        };
    }

    /**
     * Enforce maximum length for a specific field (multibyte-aware).
     *
     * Error code format: FIELD_TOO_LONG (uppercased).
     * We pre-register defaults for subject/message/phone; for custom fields add your own mapping.
     *
     * @param string $field  Property/meta name (e.g., 'subject', 'message').
     * @param int    $maxLen Maximum allowed length in characters.
     * @return callable(ContactDataValidator): void
     */
    public static function length(string $field, int $maxLen): callable
    {
        return function (ContactDataValidator $d) use ($field, $maxLen): void {
            $value = (string) self::value($d, $field);
            if ($value !== '' && mb_strlen($value, 'UTF-8') > $maxLen) {
                throw new ValidationException(strtoupper($field) . '_TOO_LONG', $field, '');
            }
        };
    }

    // -----------------
    // Internal helpers
    // -----------------

    /**
     * Read a field's value from the read-only snapshot by common name.
     * Falls back to meta[...] for custom fields.
     *
     * @param ContactDataValidator $d
     * @param string               $field
     * @return string
     */
    private static function value(ContactDataValidator $d, string $field): string
    {
        $map = [
            'name'    => 'getName',
            'email'   => 'getEmail',
            'subject' => 'getSubject',
            'message' => 'getMessage',
            'phone'   => 'getPhone',
        ];

        if (isset($map[$field]) && \is_callable([$d, $map[$field]])) {
            /** @phpstan-ignore-next-line */
            return (string) $d->{$map[$field]}();
        }

        // Fallback to meta
        $meta = (array) $d->getMeta();
        if (array_key_exists($field, $meta)) {
            $v = $meta[$field];
            return is_scalar($v) ? (string) $v : '';
        }

        return '';
    }
}
