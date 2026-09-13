<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Support;

use rafalmasiarek\ContactForm\Core\Codes;
use rafalmasiarek\ContactForm\Contracts\MessageResolverInterface;

/**
 * ArrayMessageResolver
 *
 * Lightweight message resolver with optional suggested HTTP status.
 *
 * Descriptor format (internal storage):
 *   [
 *     'message' => string,   // human-friendly text
 *     'http'    => int?,     // optional suggested HTTP status
 *   ]
 *
 * Backward-compatible inputs accepted by extend()/constructor:
 *   - string                                 → treated as ['message' => <string>]
 *   - array with keys:
 *       * 'message' | 'msg' | 'errstr' | 'title'  → normalized to 'message'
 *       * 'http' | 'status'                      → normalized to 'http'
 *       * any 'errco' provided is IGNORED (we no longer store numeric app codes)
 */
final class ArrayMessageResolver implements MessageResolverInterface
{
    /** Fallback text for any descriptor that would otherwise carry no message. */
    private const UNEXPECTED_ERROR_MESSAGE = 'Unexpected error.';

    /**
     * @var array<string, array{message:string, http?:int}>
     */
    private array $map = [];

    /**
     * @param array<string, string|array<string, mixed>> $map
     */
    public function __construct(array $map = [])
    {
        // Core defaults (service-level, transport-agnostic).
        $this->extend([
            Codes::OK_SENT         => ['message' => 'Thanks! Your message has been sent.', 'http' => 200],
            Codes::ERR_VALIDATION  => ['message' => 'Validation failed.',                  'http' => 422],
            Codes::ERR_NO_SENDER   => ['message' => 'Email sender is not configured.',      'http' => 500],
            Codes::ERR_SEND_FAILED => ['message' => 'Message could not be sent.',          'http' => 502],
            Codes::ERR_UNEXPECTED  => ['message' => self::UNEXPECTED_ERROR_MESSAGE, 'http' => 500],
        ]);

        if ($map !== []) {
            $this->extend($map);
        }
    }

    /**
     * Resolve code to a human-friendly message.
     * Unknown codes fall back to the unexpected-error message.
     *
     * @param string               $code
     * @param array<int|string>    $context Values for vsprintf() placeholders (optional).
     * @return string
     */
    public function resolve(string $code, array $context = []): string
    {
        $tpl = $this->describe($code)['message'];
        return $context ? \vsprintf($tpl, $context) : $tpl;
    }

    /**
     * Describe a code (message + optional suggested HTTP).
     * Unknown codes fall back to the ERR_UNEXPECTED descriptor — never the raw
     * code string, which would otherwise leak an internal identifier as if it
     * were a human-readable message.
     *
     * @param string $code
     * @return array{message:string, http?:int}
     */
    public function describe(string $code): array
    {
        return $this->map[$code] ?? $this->map[Codes::ERR_UNEXPECTED];
    }

    /**
     * Return all registered descriptors keyed by error code.
     *
     * @return array<string, array{message:string, http?:int}>
     */
    public function all(): array
    {
        return $this->map;
    }

    /**
     * Merge descriptors into resolver (normalizing flexible input).
     *
     * @param array<string, string|array<string, mixed>> $map
     * @return void
     */
    public function extend(array $map): void
    {
        foreach ($map as $code => $value) {
            $this->map[$code] = $this->normalizeDescriptor($code, $value);
        }
    }

    /**
     * Normalize flexible input into a strict descriptor used internally.
     *
     * Accepted inputs:
     *  - string → ['message' => <string>]
     *  - array  → keys normalized:
     *      * 'message' | 'msg' | 'errstr' | 'title' → 'message'
     *      * 'http' | 'status'                      → 'http' (int)
     *      * 'errco' is ignored (no longer stored)
     *
     * @param string                      $code
     * @param string|array<string, mixed> $value
     * @return array{message:string, http?:int}
     */
    private function normalizeDescriptor(string $code, string|array $value): array
    {
        if (\is_string($value)) {
            return ['message' => $value !== '' ? $value : self::UNEXPECTED_ERROR_MESSAGE];
        }

        // message
        $message = $value['message']
            ?? $value['msg']
            ?? $value['errstr']
            ?? (isset($value['title']) ? (string)$value['title'] : '');

        if (!\is_string($message) || $message === '') {
            $message = self::UNEXPECTED_ERROR_MESSAGE;
        }

        // http (ignore errco entirely)
        $http = $value['http'] ?? $value['status'] ?? null;
        $desc = ['message' => $message];

        if (\is_int($http)) {
            $desc['http'] = $http;
        }

        return $desc;
    }
}
