<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Core;

use rafalmasiarek\ContactForm\Model\ContactData;

/**
 * Thin read-write façade around the live {@see ContactData} DTO 
 * plus a lightweight context bag for hooks.
 *
 * This wrapper intentionally exposes only a single accessor {@see data()}
 * to keep the contract simple and explicit: hooks can freely read and
 * modify the underlying mutable ContactData object.
 * 
 * Hooks can mutate the DTO via data() and read request context (e.g. raw body)
 * via body() / ctx().
 *
 * Rationale:
 * - Makes the "hook API surface" explicit (hooks operate on a wrapper).
 * - Keeps validators separate (they should use a read-only snapshot).
 *
 * Example:
 * <code>
 * public function onBeforeValidate(ContactDataHook $d): void {
 *     $data = $d->data();
 *     $data->name = trim($data->name);
 *     $data->meta['normalized'] = true;
 * }
 * </code>
 */
final class ContactDataHook
{
    /** @var ContactData */
    private ContactData $data;

    /**
     * Arbitrary, read-only context for hooks (e.g. ["body"=>[...], "ip"=>...]).
     * @var array<string,mixed>
     */
    private array $context;

    /**
     * @param ContactData               $data
     * @param array<string,mixed>       $context  Optional extras like ['body'=>array, ...]
     */
    public function __construct(ContactData $data, array $context = [])
    {
        $this->data    = $data;
        $this->context = $context;
    }

    /**
     * Access the underlying mutable DTO.
     */
    public function data(): ContactData
    {
        return $this->data;
    }

    /**
     * Convenience: return the original parsed request body (if provided).
     *
     * @return array<mixed>
     */
    public function body(): array
    {
        $b = $this->context['body'] ?? [];
        return \is_array($b) ? $b : [];
    }

    /**
     * Generic context accessor.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    public function ctx(string $key, $default = null)
    {
        return $this->context[$key] ?? $default;
    }
}
