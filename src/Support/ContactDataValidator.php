<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Support;


use rafalmasiarek\ContactForm\Model\ContactData;
/**
 * Immutable, read-only snapshot view of {@see ContactData} for validators.
 *
 * This class is intended to be constructed via {@see ContactDataValidator::from()}
 * immediately before running the validation pipeline. It captures the current state
 * of the mutable {@see ContactData} into an immutable structure so that:
 *
 * - Hooks can still freely mutate the live {@see ContactData} object.
 * - Validators operate on a stable, read-only snapshot, preventing accidental writes.
 *
 * Notes:
 * - All scalar fields are copied to strings (empty string when missing).
 * - {@see getMeta()} returns a defensive (deep) copy to avoid external mutation.
 * - There are no setters by design; only accessor methods are exposed.
 *
 * Typical usage:
 *
 * <code>
 * $snapshot = ContactDataValidator::from($contactData);
 * $email    = $snapshot->getEmail(); // read-only
 * </code>
 *
 * @see ContactData
 */
final class ContactDataValidator
{
    /**
     * Contact person's display name as provided by the submitter.
     *
     * Always a string; empty string when absent.
     *
     * @var string
     */
    private string $name;

    /**
     * Sender's email address (unvalidated raw input).
     *
     * Always a string; empty string when absent.
     *
     * @var string
     */
    private string $email;

    /**
     * Message subject/title supplied by the user.
     *
     * Always a string; empty string when absent.
     *
     * @var string
     */
    private string $subject;

    /**
     * Main message body supplied by the user.
     *
     * Always a string; empty string when absent.
     *
     * @var string
     */
    private string $message;

    /**
     * Optional phone number supplied by the user (raw, unnormalized).
     *
     * Always a string; empty string when absent.
     *
     * @var string
     */
    private string $phone;

    /**
     * Arbitrary metadata captured from {@see ContactData::$meta}.
     *
     * This is a deep-copied array to prevent external mutation of nested structures.
     *
     * @var array<string,mixed>
     */
    private array $meta;

    /**
     * Arbitrary body captured from {@see ContactData::$body}.
     *
     * This is a deep-copied array to prevent external mutation of nested structures.
     *
     * @var array<string,mixed>
     */
    private array $body;


    /**
     * Create an immutable, read-only snapshot from the mutable {@see ContactData}.
     *
     * Each scalar field is normalized to a string (empty string when not set),
     * and {@see ContactData::$meta} is deep-copied to ensure immutability.
     *
     * @param ContactData $d Mutable DTO to snapshot.
     * @return self Read-only snapshot for validators.
     */
    public static function from(ContactData $d): self
    {
        $v = new self();
        $v->name    = (string)($d->name ?? '');
        $v->email   = (string)($d->email ?? '');
        $v->subject = (string)($d->subject ?? '');
        $v->message = (string)($d->message ?? '');
        $v->phone   = (string)($d->phone ?? '');
        $v->meta    = \is_array($d->meta ?? null) ? self::deepCopyArray($d->meta) : [];
        $v->body    = \is_array($d->body ?? null) ? self::deepCopyArray($d->body) : [];
        return $v;
    }

    /**
     * Recursively copy an array to produce a defensive clone.
     *
     * This prevents validators or downstream consumers from mutating nested
     * metadata structures by reference.
     *
     * @param array<string,mixed> $a Source array.
     * @return array<string,mixed> Deep copy of the source array.
     */
    private static function deepCopyArray(array $a): array
    {
        $copy = [];
        foreach ($a as $k => $v) {
            $copy[$k] = \is_array($v) ? self::deepCopyArray($v) : $v;
        }
        return $copy;
    }

    /**
     * Get the submitter's name (read-only).
     *
     * @return string Non-null string (may be empty).
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the submitter's email address (read-only, unvalidated).
     *
     * @return string Non-null string (may be empty).
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Get the message subject/title (read-only).
     *
     * @return string Non-null string (may be empty).
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * Get the message body (read-only).
     *
     * @return string Non-null string (may be empty).
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Get the phone number (read-only, unnormalized).
     *
     * @return string Non-null string (may be empty).
     */
    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * Get a defensive (deep) copy of the metadata bag.
     *
     * Modifications to the returned array will NOT affect the internal snapshot.
     *
     * @return array<string,mixed> Deep-copied metadata.
     */
    public function getMeta(): array
    {
        return self::deepCopyArray($this->meta);
    }

    /**
     * Get a defensive (deep) copy of the body bag.
     *
     * Modifications to the returned array will NOT affect the internal snapshot.
     *
     * @return array<string,mixed> Deep-copied metadata.
     */
    public function getBody(): array
    {
        return self::deepCopyArray($this->body);
    }
}
