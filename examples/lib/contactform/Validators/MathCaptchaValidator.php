<?php

declare(strict_types=1);

namespace ContactForm\Validators;

use rafalmasiarek\ContactForm\Support\ArrayMessageResolver;
use rafalmasiarek\ContactForm\Support\ContactDataValidator;
use rafalmasiarek\ContactForm\Core\ValidationException;

/**
 * Math CAPTCHA validator (factory) compatible with setValidators([...]).
 *
 * It compares a user-provided numeric answer (from a form field)
 * with the expected value stored in the PHP session (e.g. under "captcha_expected").
 *
 * Usage:
 *   // 1) Register default messages (once during wiring)
 *   MathCaptchaValidator::registerDefaultMessages($resolver);
 *
 *   // 2) Generate a challenge for the GET form (store expected answer in session)
 *   $question = MathCaptchaValidator::generateChallenge('captcha_expected'); // e.g. "3 × 7 = ?"
 *
 *   // 3) Add validator for POST
 *   $svc->setValidators([
 *     'captcha' => MathCaptchaValidator::validate(
 *         field: 'captcha_answer',      // name of input field in the form
 *         sessionKey: 'captcha_expected',
 *         oneShot: true                 // unset expected value after first check
 *     ),
 *   ]);
 */
final class MathCaptchaValidator
{
    /** Error code: expected answer is missing (no challenge generated or session lost). */
    public const CAPTCHA_MISSING = 'CAPTCHA_MISSING';

    /** Error code: provided answer does not match the expected result. */
    public const CAPTCHA_INVALID = 'CAPTCHA_INVALID';

    /**
     * Register default human-readable messages for the resolver.
     *
     * @param ArrayMessageResolver $resolver
     * @return void
     */
    public static function registerDefaultMessages(ArrayMessageResolver $resolver): void
    {
        $resolver->extend([
            self::CAPTCHA_MISSING => ['message' => 'Captcha token is missing or expired.',  'http' => 422],
            self::CAPTCHA_INVALID => ['message' => 'Captcha answer is invalid.',            'http' => 422],
        ]);
    }

    /**
     * Build a validator that checks a numeric answer from a form field
     * against the expected value stored in $_SESSION[$sessionKey].
     *
     * @param string $field       Form field name containing the user's answer (e.g. "captcha_answer").
     * @param string $sessionKey  Session key where the expected answer is stored (default: "captcha_expected").
     * @param bool   $oneShot     When true, the expected answer is unset after validation.
     * @return callable(ContactDataValidator): void
     */
    public static function validate(string $field = 'captcha_answer', string $sessionKey = 'captcha_expected', bool $oneShot = true): callable
    {
        return function (ContactDataValidator $data) use ($field, $sessionKey, $oneShot): void {
            $expected = isset($_SESSION[$sessionKey]) ? (string)$_SESSION[$sessionKey] : '';

            if ($oneShot && isset($_SESSION[$sessionKey])) {
                unset($_SESSION[$sessionKey]);
            }

            if ($expected === '') {
                throw new ValidationException(self::CAPTCHA_MISSING, $field, '');
            }

            $provided = self::value($data, $field);

            // Normalize to integer comparison; allow leading zeros and whitespace.
            $normalize = static function (string $v): string {
                $v = \trim($v);
                // Accept optional leading '+' or '-' and digits only
                if ($v === '' || !\preg_match('/^[\+\-]?\d+$/', $v)) {
                    // Force non-numeric to a non-matching sentinel
                    return '__NON_NUMERIC__';
                }
                // Normalize numeric string (preserve sign)
                return (string)((int)$v);
            };

            if ($normalize($provided) !== $normalize($expected)) {
                throw new ValidationException(self::CAPTCHA_INVALID, $field, '', ['provided' => $provided]);
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
