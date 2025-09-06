<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Core;

/**
 * Centralized list of stable symbolic codes used by the core service.
 * Transport-specific codes (e.g., SMTP) must live inside the transport implementation
 * to avoid leaking implementation details globally.
 */
final class Codes
{
    // Success
    public const OK_SENT         = 'OK_SENT';

    // Generic errors (transport-agnostic)
    public const ERR_VALIDATION  = 'ERR_VALIDATION';
    public const ERR_NO_SENDER   = 'ERR_NO_SENDER';
    public const ERR_SEND_FAILED = 'ERR_SEND_FAILED';
    public const ERR_UNEXPECTED  = 'ERR_UNEXPECTED';
}
