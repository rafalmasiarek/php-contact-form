<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Core;

/**
 * Generic validation exception for contact form validators.
 *
 * - $code: short, machine-readable error code (e.g. EMAIL_INVALID, NAME_REQUIRED)
 * - $field: optional field name related to the error (e.g. "email")
 * - $meta: optional extra details (remain internal / for logging)
 */
final class ValidationException extends \RuntimeException
{
    /** @var string */
    private string $errorCode;

    /** @var string|null */
    private ?string $field;

    /** @var array<string,mixed> */
    private array $meta;

    /**
     * @param string               $errorCode  Machine-readable code, e.g. 'EMAIL_INVALID'
     * @param string|null          $field      Related field name, e.g. 'email'
     * @param string               $message    Optional human message (defaults to $errorCode)
     * @param array<string,mixed>  $meta       Optional extra details for logs/diagnostics
     * @param int                  $code       Native exception code
     * @param \Throwable|null      $previous   Previous exception
     */
    public function __construct(
        string $errorCode,
        ?string $field = null,
        string $message = '',
        array $meta = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message === '' ? $errorCode : $message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->field     = $field;
        $this->meta      = $meta;
    }

    /** Machine-readable error code (e.g., EMAIL_INVALID). */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** Field name related to the validation error, if any. */
    public function getField(): ?string
    {
        return $this->field;
    }

    /** Extra details (for logging/diagnostics). */
    public function getMeta(): array
    {
        return $this->meta;
    }
}
