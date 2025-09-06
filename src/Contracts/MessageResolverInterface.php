<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;

/**
 * Resolves a human-friendly message and (optionally) structured metadata for a code.
 *
 * Implementations may support structured descriptors including HTTP/app error codes.
 */
interface MessageResolverInterface
{
    /**
     * Resolve a UI-friendly message for a given code.
     *
     * @param string                   $code    Stable symbolic code (OK_* / ERR_*).
     * @param array<int|string, mixed> $context Optional vsprintf context.
     */
    public function resolve(string $code, array $context = []): string;

    /**
     * Describe a code with optional metadata used by the API layer.
     *
     * Keys:
     * - message (string)  Human-readable message
     * - http    (int)     Suggested HTTP status (API may default if missing)
     * - errco   (int)     Numeric application error code
     *
     * @return array{message:string, http?:int, errco?:int}
     */
    public function describe(string $code): array;

    /**
     * Extend/override code mappings.
     *
     * Values may be either:
     *  - string → message only
     *  - array{message?:string|int, msg?:string, errstr?:string, http?:int, status?:int, errco?:int, code?:int}
     *    (keys are normalized; errco/http/status/code are accepted)
     *
     * @param array<string, string|array<string, mixed>> $map
     */
    public function extend(array $map): void;
}
