<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Contracts;


use rafalmasiarek\ContactForm\Core\ContactDataHook;
/**
 * Lifecycle hooks for the contact form pipeline.
 *
 * Hooks receive a read-write façade ({@see ContactDataHook}) that exposes the
 * live {@see ContactData}. Use hooks to normalize/enrich the payload,
 * add diagnostics, or perform side effects (metrics, auditing, etc.).
 *
 * IMPORTANT:
 * - Hooks may mutate data via ContactDataHook.
 * - Validators must operate on the read-only snapshot ({@see ContactDataValidator}).
 */
interface ContactFormHookInterface
{
    /**
     * Invoked just before validators are executed.
     * You may mutate the payload (e.g., normalize fields, enrich meta) or throw to abort.
     *
     * @param ContactDataHook $d Read-write access to the live ContactData.
     * @return void
     *
     * @throws \Throwable To abort the pipeline with a custom reason.
     */
    public function onBeforeValidate(ContactDataHook $d): void;

    /**
     * NEW: Invoked after all validators have succeeded, but before the email
     * is rendered and sent. This is the right place to annotate body/meta based
     * on validator outputs (available in $validatorsMeta) without repeating
     * the validator's work.
     *
     * The $validatorsMeta bag is what the service collected under:
     *   $service->meta['validators']  (per-validator namespace)
     *
     * Example shape:
     *   [
     *     'csrfValidator'  => ['status'=>'ok',   ...],
     *     'emailValidator' => ['status'=>'ok',   ...],
     *     'threatScan'     => ['status'=>'pass', 'summary'=>{...}],
     *     ...
     *   ]
     *
     * @param ContactDataHook     $d              Read-write access to the live ContactData.
     * @param array<string,mixed> $validatorsMeta Aggregated meta produced by validators.
     * @return void
     */
    public function onAfterValidate(ContactDataHook $d, array $validatorsMeta): void;

    /**
     * Invoked after a successful send.
     *
     * @param ContactDataHook $d Read-write access to the live ContactData.
     * @param string          $messageId Optional transport message id (if any).
     * @return void
     */
    public function onAfterSend(ContactDataHook $d, string $messageId = ''): void;

    /**
     * Invoked if the send operation fails with an exception.
     *
     * @param ContactDataHook $d Read-write access to the live ContactData.
     * @param \Throwable      $e The failure exception.
     * @return void
     */
    public function onSendFailure(ContactDataHook $d, \Throwable $e): void;
}
