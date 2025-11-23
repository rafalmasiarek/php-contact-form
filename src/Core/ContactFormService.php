<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Core;

use rafalmasiarek\ContactForm\Contracts\MessageResolverInterface;
use rafalmasiarek\ContactForm\Contracts\EmailSenderInterface;
use rafalmasiarek\ContactForm\Contracts\AttemptLoggerInterface;
use rafalmasiarek\ContactForm\Contracts\ContactFormHookInterface;
use rafalmasiarek\ContactForm\Contracts\EmailTemplateInterface;

use rafalmasiarek\ContactForm\Core\ContactDataHook;
use rafalmasiarek\ContactForm\Core\Codes;
use rafalmasiarek\ContactForm\Core\ValidationException;

use rafalmasiarek\ContactForm\Model\ContactData;
use rafalmasiarek\ContactForm\Model\OutboundEmail;

use rafalmasiarek\ContactForm\Support\ContactDataValidator;
use rafalmasiarek\ContactForm\Support\ArrayMessageResolver;
use rafalmasiarek\ContactForm\Support\NullLogger;

use rafalmasiarek\ContactForm\Mail\PhpMailerEmailSender;

/**
 * Lightweight, pluggable contact form service.
 *
 * Responsibilities:
 * - Runs user-provided validators (callables) that may throw on error.
 * - Renders an HTML email from {@see ContactData}.
 * - Sends the message via an injected {@see EmailSenderInterface} (or a lazy PHPMailer sender if SMTP config is provided).
 * - Logs structured events using {@see AttemptLoggerInterface}.
 * - Retrieves request metadata (IP/UA) via {@see IpResolverInterface}.
 *
 * Notes:
 * - The service returns a simple array payload, not an HTTP response.
 * - External checks (CSRF / reCAPTCHA / geoblocking) should happen upstream.
 * - Message text is resolved from a symbolic code via {@see MessageResolverInterface}.
 */
final class ContactFormService
{
    /**
     * List of validator callables executed before sending.
     * Each validator receives ContactData and may throw to abort.
     *
     * @var array<string, callable(ContactDataValidator):(void|array<string,mixed>)>
     */
    private array $validators = [];

    /** @var AttemptLoggerInterface Logger implementation (defaults to {@see NullLogger}). */
    private AttemptLoggerInterface $logger;

    /** @var array<string,mixed> */
    private array $context = [];

    /**
     * Hooks executed around the send pipeline.
     * Methods may optionally return arrays with meta which will be collected.
     *
     * @var ContactFormHookInterface[]
     */
    private array $hooks = [];

    /** @var ContactFormHookInterface[] Hooks run globally before any validator */
    private array $hooksBeforeGlobal = [];

    /** @var array<string, ContactFormHookInterface[]> Hooks run before specific validator label */
    private array $hooksBeforeByValidator = [];

    /** @var ContactFormHookInterface[] Hooks run once after all validators */
    private array $hooksAfterGlobal = [];

    /** @var array<string, ContactFormHookInterface[]> Hooks run right after specific validator label */
    private array $hooksAfterByValidator = [];

    /** @var EmailSenderInterface|null Optional sender; falls back to PHPMailer sender if SMTP config is provided. */
    private ?EmailSenderInterface $emailSender = null;

    /**
     * Optional template used to generate the HTML and plain-text bodies.
     *
     * @var EmailTemplateInterface|null
     */
    private ?EmailTemplateInterface $template = null;

    /**
     * SMTP configuration used to lazily construct a default PHPMailer sender
     * when no custom {@see EmailSenderInterface} is injected.
     *
     * @var array<string,mixed>|null
     */
    private ?array $smtpConfig = null;

    /** Message resolver for code → text mapping. */
    private MessageResolverInterface $messages;

    /**
     * Aggregated meta bag collected from validators, hooks and request data.
     *
     * @var array<string,mixed>
     */
    private array $meta = [];

    /**
     * @param array<string,mixed>|null $smtpConfig Optional SMTP config for lazy PHPMailer sender.
     */
    public function __construct(?array $smtpConfig = null)
    {
        $this->logger     = new NullLogger();
        $this->smtpConfig = $smtpConfig;
        $this->messages   = new ArrayMessageResolver(); // default EN; inject your own for localization
    }

    /**
     * Store the original parsed request body for hook access.
     *
     * @return $this
     */
    public function withRequestBody(array $body): self
    {
        $this->context['body'] = $body;
        return $this;
    }

    /**
     * (Optional) Generic context setter to add client/server/request info.
     *
     * @param array<string,mixed> $ctx
     * @return $this
     */
    public function withContext(array $ctx): self
    {
        // later context wins
        $this->context = $ctx + $this->context;
        return $this;
    }

    /**
     * Replace the full validator set.
     *
     * Accepted forms:
     *  - ['label' => callable]
     *  - [callable, callable] (labels auto-derived)
     *
     * @param array<string|int, callable> $validators
     * @return $this
     */
    public function setValidators(array $validators): self
    {
        $this->validators = [];
        foreach ($validators as $k => $v) {
            if (!\is_callable($v)) {
                throw new \InvalidArgumentException('Validator must be callable.');
            }
            $label = \is_string($k) ? $k : $this->callableLabel($v);
            $this->validators[$label] = $v;
        }
        return $this;
    }

    /**
     * Add a single validator callable (optionally with a fixed name).
     *
     * @param callable(ContactDataValidator):(void|array<string,mixed>) $validator
     * @param string|null $name
     * @return $this
     */
    public function addValidator(callable $validator, ?string $name = null): self
    {
        if (!\is_callable($validator)) {
            throw new \InvalidArgumentException('Validator must be callable.');
        }
        $name = $name !== null && $name !== '' ? $name : $this->callableLabel($validator);
        $this->validators[$name] = $validator;
        return $this;
    }

    /**
     * Set a custom logger.
     *
     * @return $this
     */
    public function setLogger(AttemptLoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Backward-compat: set a single hook (overwrites existing).
     * Prefer setHooks()/addHook() for multiple hooks.
     *
     * @deprecated Use setHooks() or addHook() instead.
     * @return $this
     */
    public function setHook(ContactFormHookInterface $hook): self
    {
        $this->hooks = [$hook];
        return $this;
    }

    /**
     * Replace all hooks at once (order preserved).
     *
     * Each item may be:
     *  - ContactFormHookInterface instance (global: before-all & after-all),
     *  - array spec:
     *      [ ContactFormHookInterface $hook, 'beforeValidator' => string|string[]|null, 'afterValidator' => string|string[]|null ]
     *    Empty array [] for before/after means "global" in that phase.
     *
     * @param array<int, ContactFormHookInterface|array> $hooks
     * @return $this
     */
    public function setHooks(array $hooks): self
    {
        // reset
        $this->hooks = [];
        $this->hooksBeforeGlobal = [];
        $this->hooksAfterGlobal = [];
        $this->hooksBeforeByValidator = [];
        $this->hooksAfterByValidator = [];

        foreach ($hooks as $spec) {
            if ($spec instanceof ContactFormHookInterface) {
                // plain: global before + global after + afterSend/failure
                $this->hooks[] = $spec;
                $this->hooksBeforeGlobal[] = $spec;
                $this->hooksAfterGlobal[]  = $spec;
                continue;
            }

            if (\is_array($spec) && isset($spec[0]) && $spec[0] instanceof ContactFormHookInterface) {
                /** @var ContactFormHookInterface $hook */
                $hook = $spec[0];
                $this->hooks[] = $hook; // always keep for afterSend / onFailure

                // beforeValidator
                if (\array_key_exists('beforeValidator', $spec)) {
                    $bv = $spec['beforeValidator'];
                    if ($bv === [] || $bv === '' || $bv === null) {
                        $this->hooksBeforeGlobal[] = $hook; // global before
                    } else {
                        $labels = \is_array($bv) ? $bv : [$bv];
                        foreach ($labels as $lbl) {
                            $lbl = (string)$lbl;
                            $this->hooksBeforeByValidator[$lbl][] = $hook;
                        }
                    }
                } else {
                    // default: global before
                    $this->hooksBeforeGlobal[] = $hook;
                }

                // afterValidator
                if (\array_key_exists('afterValidator', $spec)) {
                    $av = $spec['afterValidator'];
                    if ($av === [] || $av === '' || $av === null) {
                        $this->hooksAfterGlobal[] = $hook; // global after
                    } else {
                        $labels = \is_array($av) ? $av : [$av];
                        foreach ($labels as $lbl) {
                            $lbl = (string)$lbl;
                            $this->hooksAfterByValidator[$lbl][] = $hook;
                        }
                    }
                } else {
                    // default: global after
                    $this->hooksAfterGlobal[] = $hook;
                }

                continue;
            }

            throw new \InvalidArgumentException('Invalid hook definition.');
        }

        return $this;
    }

    /**
     * Append a hook (executed after existing ones).
     *
     * @return $this
     */
    public function addHook(ContactFormHookInterface $hook): self
    {
        $this->hooks[] = $hook;
        return $this;
    }

    /**
     * Inject a custom email sender implementation.
     *
     * @return $this
     */
    public function setEmailSender(EmailSenderInterface $sender): self
    {
        $this->emailSender = $sender;
        return $this;
    }

    /**
     * Replace SMTP configuration (used only if no custom sender is injected).
     *
     * @param array<string,mixed>|null $smtp
     * @return $this
     */
    public function setSmtpConfig(?array $smtp): self
    {
        $this->smtpConfig = $smtp;
        return $this;
    }

    /**
     * Inject a custom message resolver (for i18n / per-project copy).
     *
     * @return $this
     */
    public function setMessageResolver(MessageResolverInterface $resolver): self
    {
        $this->messages = $resolver;
        return $this;
    }

    /**
     * Execute the full submission pipeline.
     *
     * Return shape (intentionally minimal):
     *   - on success: ['ok' => true,  'code' => 'OK_SENT',        'message' => ..., 'meta' => {...}]
     *   - on error:   ['ok' => false, 'code' => 'ERR_* / custom', 'message' => ..., 'meta' => {...}]
     *
     * Meta contains outputs from validators and hooks (if they return arrays),
     * as well as basic request metadata (ip/ua) when available.
     *
     * @param  ContactData $data Submitted contact data.
     * @return array{ok:bool, code:string, message:string, meta:array<string,mixed>}
     */
    public function process(ContactData $data): array
    {
        // Attach selected client/server context into meta (if provided)
        if (isset($this->context['client']) && is_array($this->context['client'])) {
            $client = $this->context['client'];
            $allowed = ['ip', 'ua', 'country', 'request_id'];
            foreach ($allowed as $k) {
                if (isset($client[$k]) && $client[$k] !== '') {
                    $this->meta['client'][$k] = $client[$k];
                }
            }
        }
        if (isset($this->context['server']) && is_array($this->context['server'])) {
            // keep it light; don't expose full server map unless you want to in your app
            if (isset($this->context['server']['HTTP_REFERER'])) {
                $this->meta['server']['referer'] = (string)$this->context['server']['HTTP_REFERER'];
            }
        }

        try {
            // -------------------------------
            // 1) Hooks: onBeforeValidate()
            // -------------------------------
            $hookDto = new ContactDataHook($data, $this->context);
            // global "before all" hooks
            foreach ($this->hooksBeforeGlobal as $h) {
                try {
                    $h->onBeforeValidate($hookDto);
                } catch (\Throwable $ignored) {
                }
            }

            // --------------------------------
            // 2) Validators: read-only snapshot
            // --------------------------------
            foreach ($this->validators as $label => $validator) {
                if (!empty($this->hooksBeforeByValidator[$label])) {
                    foreach ($this->hooksBeforeByValidator[$label] as $h) {
                        try {
                            $h->onBeforeValidate($hookDto);
                        } catch (\Throwable $ignored) {
                        }
                    }
                }

                $ro = ContactDataValidator::from($data);

                try {
                    $ret  = $validator($ro);
                    $item = \is_array($ret) ? $ret : ['status' => 'OK'];

                    $this->meta['validators'][$label][] = $item;

                    if (!empty($this->hooksAfterByValidator[$label])) {
                        $subset = [$label => $this->meta['validators'][$label]];
                        foreach ($this->hooksAfterByValidator[$label] as $h) {
                            try {
                                $h->onAfterValidate($hookDto, $subset);
                            } catch (\Throwable $ignored) {
                            }
                        }
                    }
                } catch (ValidationException $e) {
                    // Record failing validator meta before propagating the exception.
                    $failMeta = [
                        'status'     => 'FAIL',
                        'error_code' => $e->getErrorCode(),
                        'field'      => $e->getField(),
                    ];

                    $exMeta = $e->getMeta();
                    if (\is_array($exMeta) && $exMeta !== []) {
                        $failMeta['meta'] = $exMeta;
                    }

                    $this->meta['validators'][$label][] = $failMeta;

                    if (!empty($this->hooksAfterByValidator[$label])) {
                        $subset = [$label => $this->meta['validators'][$label]];
                        foreach ($this->hooksAfterByValidator[$label] as $h) {
                            try {
                                $h->onAfterValidate($hookDto, $subset);
                            } catch (\Throwable $ignored) {
                            }
                        }
                    }

                    // Rethrow so that global error handling and message mapping remain the same.
                    throw $e;
                }
            }

            // 2.5) After-validate hooks: annotate based on validators' meta (no new scanning).
            // Hooks get read-write access to ContactData and a compact per-validator bag.
            $allMeta = $this->meta['validators'] ?? [];
            foreach ($this->hooksAfterGlobal as $h) {
                try {
                    $h->onAfterValidate($hookDto, $allMeta);
                } catch (\Throwable $ignored) {
                }
            }

            // --------------------------------
            // 3) Build & send the email
            // --------------------------------
            $sender = $this->emailSender ?? $this->makeDefaultSender();
            if (!$sender) {
                // map to your ERR_NO_SENDER etc.
                $code = Codes::ERR_NO_SENDER;
                return $this->fail($code, $this->messages->resolve($code), array_merge($data->meta, $this->meta));
            }

            // Legacy call style for outbound email building.
            $email = OutboundEmail::fromContactData(
                $data,
                $this->smtpConfig ?? [],
                $this->template,
                is_array($data->meta) && isset($data->meta['attachments']) ? (array)$data->meta['attachments'] : []
            );

            $sender->send($email);

            $this->logger->info('contact.send.ok', ['to' => $email->to]);

            // -------------------------------
            // 4) Hooks: onAfterSend()
            // -------------------------------
            foreach ($this->hooks as $h) {
                try {
                    /** @var ContactDataHook $hookDto Hook wrapper around ContactData and context. */
                    $h->onAfterSend($hookDto, '');
                } catch (\Throwable $ignored) {
                    // swallow hook errors post-send
                }
            }

            $code = Codes::OK_SENT;
            return $this->success($code, $this->messages->resolve($code), array_merge($data->meta, $this->meta));
        } catch (ValidationException $e) {
            $this->logger->warning('contact.validation.fail', [
                'code'  => $e->getErrorCode(),
                'field' => $e->getField(),
                'meta'  => $e->getMeta(),
            ]);

            $code = $e->getErrorCode() ?: Codes::ERR_VALIDATION;
            return $this->fail($code, $this->messages->resolve($code, $e->getMeta()), array_merge($data->meta, $this->meta));
        } catch (\Throwable $e) {
            $this->logger->error('contact.send.fail', ['error' => $e->getMessage()]);

            // -------------------------------
            // Hooks: onSendFailure()
            // -------------------------------
            foreach ($this->hooks as $h) {
                try {
                    /** @var ContactDataHook $hookDto Hook wrapper around ContactData and context. */
                    $h->onSendFailure($hookDto, $e);
                } catch (\Throwable $ignored) {
                    // swallow hook errors after failure
                }
            }

            $code = Codes::ERR_SEND_FAILED;
            return $this->fail($code, $this->messages->resolve($code), array_merge($data->meta, $this->meta));
        }
    }

    /**
     * Lazily construct the default PHPMailer-based sender if SMTP config is present.
     *
     * @return EmailSenderInterface|null
     */
    private function makeDefaultSender(): ?EmailSenderInterface
    {
        if ($this->emailSender) {
            return $this->emailSender;
        }
        if ($this->smtpConfig) {
            return new PhpMailerEmailSender($this->smtpConfig);
        }
        return null;
    }

    /**
     * Build a success response envelope.
     *
     * @param string               $code     Stable symbolic code, e.g. Codes::OK_SENT
     * @param string               $message  Human-friendly message resolved by the resolver.
     * @param array<string,mixed>  $meta     Aggregated meta to return to the caller.
     * @return array{ok:true, code:string, message:string, meta:array<string,mixed>}
     */
    private function success(string $code, string $message, array $meta = []): array
    {
        return [
            'ok'      => true,
            'code'    => $code,
            'message' => $message,
            'meta'    => $meta,
        ];
    }

    /**
     * Build a failure response envelope.
     *
     * @param string               $code     Stable symbolic code, e.g. 'ERR_EMAIL_INVALID' or transport-specific (e.g. PhpMailerEmailSender::ERR_SMTP_AUTH).
     * @param string               $message  Human-friendly message resolved by the resolver.
     * @param array<string,mixed>  $meta     Aggregated meta to return to the caller.
     * @return array{ok:false, code:string, message:string, meta:array<string,mixed>}
     */
    private function fail(string $code, string $message, array $meta = []): array
    {
        return [
            'ok'      => false,
            'code'    => $code,
            'message' => $message,
            'meta'    => $meta,
        ];
    }

    /**
     * Best-effort, human-readable label for a callable validator (for meta).
     *
     * @param callable $cb
     */
    private function callableLabel(callable $cb): string
    {
        if (\is_string($cb)) {
            return $cb;
        }
        if (\is_array($cb)) {
            $obj = \is_object($cb[0]) ? \get_class($cb[0]) : (string)$cb[0];
            $met = (string)$cb[1];
            return $obj . '::' . $met;
        }
        if ($cb instanceof \Closure) {
            return 'Closure@' . \spl_object_hash($cb);
        }
        if (\is_object($cb)) {
            return \get_class($cb);
        }
        return 'callable';
    }

    /**
     * Derive a stable, human-friendly key for a validator callable.
     *
     * Examples:
     *  - [ClassName, 'method']   => "ClassName.method" with namespace stripped
     *  - Closure                 => "closure.<hash8>"
     *  - "function_name" string  => "function_name"
     *  - fallback                => "callable"
     *
     * Small cosmetics:
     *  - Strip leading "ContactForm" and trailing "Validator" to shorten common names.
     */
    private function validatorKey(callable $cb): string
    {
        if (\is_array($cb)) {
            $class = \is_object($cb[0]) ? \get_class($cb[0]) : (string)$cb[0];
            $short = ($p = \strrpos($class, '\\')) !== false ? \substr($class, $p + 1) : $class;
            $method = (string)$cb[1];
            $key = $short . '.' . $method;
        } elseif ($cb instanceof \Closure) {
            $key = 'closure.' . \substr(\spl_object_hash($cb), 0, 8);
        } elseif (\is_string($cb)) {
            $key = $cb;
        } else {
            $key = 'callable';
        }

        // Optional cosmetics for your naming convention.
        $key = (string)\preg_replace('/^ContactForm/', '', $key);
        $key = (string)\preg_replace('/Validator(\.|$)/', '$1', $key);

        return $key;
    }

    /**
     * Persist per-validator meta under meta['validators'][$key].
     *
     * The stored shape is a flat associative array, e.g.:
     *   meta['validators']['csrf.validate'] = [
     *     'status' => 'ok'|'fail',
     *     'error_code' => '...',
     *     'field' => '...',
     *     'meta' => [...],
     *     ... (any extra fields emitted by the validator)
     *   ]
     *
     * @param callable               $validator The validator that produced meta.
     * @param array<string,mixed>    $meta      The metadata to store.
     * @return void
     */
    private function recordValidatorMeta(callable $validator, array $meta): void
    {
        $key = $this->validatorKey($validator);
        $this->meta['validators'][$key] = $meta;
    }
}
