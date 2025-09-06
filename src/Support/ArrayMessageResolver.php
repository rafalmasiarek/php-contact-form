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
            Codes::ERR_UNEXPECTED  => ['message' => 'Unexpected error.',                   'http' => 500],
        ]);

        if ($map !== []) {
            $this->extend($map);
        }
    }

    /**
     * Resolve code to a human-friendly message. If not found, returns the code itself.
     *
     * @param string               $code
     * @param array<int|string>    $context Values for vsprintf() placeholders (optional).
     * @return string
     */
    public function resolve(string $code, array $context = []): string
    {
        $desc = $this->describe($code);
        $tpl  = $desc['message'] ?? $code;
        return $context ? \vsprintf($tpl, $context) : $tpl;
    }

    /**
     * Describe a code (message + optional suggested HTTP).
     * Unknown codes return ['message' => $code].
     *
     * @param string $code
     * @return array{message:string, http?:int}
     */
    public function describe(string $code): array
    {
        if (!isset($this->map[$code])) {
            return ['message' => $code];
        }
        return $this->map[$code];
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
            return ['message' => $value];
        }

        // message
        $message = $value['message']
            ?? $value['msg']
            ?? $value['errstr']
            ?? (isset($value['title']) ? (string)$value['title'] : '');

        if (!\is_string($message) || $message === '') {
            $message = $code; // safe fallback
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
