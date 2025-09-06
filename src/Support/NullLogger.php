<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Support;


use rafalmasiarek\ContactForm\Contracts\AttemptLoggerInterface;
/**
 * No-op logger implementation.
 *
 * Useful as a safe default when you do not need logging.
 */
final class NullLogger implements AttemptLoggerInterface
{
    /** @inheritDoc */
    public function info(string $message, array $context = []): void {}

    /** @inheritDoc */
    public function warning(string $message, array $context = []): void {}

    /** @inheritDoc */
    public function error(string $message, array $context = []): void {}
}
