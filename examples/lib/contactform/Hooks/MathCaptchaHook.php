<?php

declare(strict_types=1);

namespace ContactForm\Hook;

use rafalmasiarek\ContactForm\Contracts\ContactFormHookInterface;
use rafalmasiarek\ContactForm\Core\ContactDataHook;

/**
 * Math CAPTCHA hook.
 *
 * Responsibility:
 * - Lift the user's answer from the HTTP request into ContactData->meta
 *   so that MathCaptchaValidator can read it from the snapshot meta bag.
 *
 * Notes:
 * - The hook prefers the contextual body provided via ContactFormService::withRequestBody(),
 *   but gracefully falls back to $_POST when the body is not supplied.
 * - Meta key defaults to the same as the request field name unless overridden.
 */
final class MathCaptchaHook implements ContactFormHookInterface
{
    /** @var string Name of the request field that carries the user's answer (e.g., "captcha_answer"). */
    private string $field;

    /** @var string Meta key under which the answer will be stored (defaults to $field). */
    private string $metaKey;

    /**
     * @param string      $field   Form field name containing the user's answer.
     * @param string|null $metaKey Optional meta key name (defaults to $field).
     */
    public function __construct(string $field = 'captcha_answer', ?string $metaKey = null)
    {
        $this->field   = $field;
        $this->metaKey = $metaKey ?: $field;
    }

    /**
     * Lift the answer from request into ContactData->meta before validators run.
     */
    public function onBeforeValidate(ContactDataHook $d): void
    {
        $answer = null;

        // Prefer request body passed via withRequestBody()
        $body = $d->body();
        if (\is_array($body) && \array_key_exists($this->field, $body)) {
            $raw = $body[$this->field];
            $answer = \is_scalar($raw) ? (string)$raw : null;
        }
        // Fallback to $_POST
        if ($answer === null && isset($_POST[$this->field])) {
            $raw = $_POST[$this->field];
            $answer = \is_scalar($raw) ? (string)$raw : null;
        }

        if ($answer !== null) {
            $d->data()->meta[$this->metaKey] = $answer;
        }
    }

    /** @param array<string,mixed> $validatorsMeta */
    public function onAfterValidate(ContactDataHook $d, array $validatorsMeta): void {}
    public function onAfterSend(ContactDataHook $d, string $messageId = ''): void {}
    public function onSendFailure(ContactDataHook $d, \Throwable $e): void {}

    /**
     * Generate a simple arithmetic challenge and store expected answer in $_SESSION.
     * The caller is responsible for starting the PHP session beforehand.
     *
     * @param string               $sessionKey  Session key to store the expected answer under.
     * @param array<int,string>    $ops         Allowed operators: '+', '-', '*'.
     * @return string                           Human-readable question, e.g. "4 × 6 = ?".
     */
    public static function generateChallenge(string $sessionKey = 'captcha_expected', array $ops = ['+', '-', '*']): string
    {
        $a   = \random_int(1, 9);
        $b   = \random_int(1, 9);
        $op  = $ops[\array_rand($ops)];

        switch ($op) {
            case '+':
                $ans = $a + $b;
                $symbol = '+';
                break;
            case '-':
                $ans = $a - $b;
                $symbol = '−';
                break;
            default:
                $ans = $a * $b;
                $symbol = '×';
                break;
        }

        $_SESSION[$sessionKey] = (string)$ans;

        return \sprintf('%d %s %d = ?', $a, $symbol, $b);
    }
}
