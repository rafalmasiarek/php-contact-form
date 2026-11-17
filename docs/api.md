## Index

### Classes

- [`Codes`](#codes) — `rafalmasiarek\ContactForm\Core`
- [`ContactDataHook`](#contactdatahook) — `rafalmasiarek\ContactForm\Core`
- [`ContactFormService`](#contactformservice) — `rafalmasiarek\ContactForm\Core`
- [`ValidationException`](#validationexception) — `rafalmasiarek\ContactForm\Core`
- [`NativeMailSender`](#nativemailsender) — `rafalmasiarek\ContactForm\Mail`
- [`PhpMailerEmailSender`](#phpmaileremailsender) — `rafalmasiarek\ContactForm\Mail`
- [`PhpMailerTransportException`](#phpmailertransportexception) — `rafalmasiarek\ContactForm\Mail`
- [`ContactData`](#contactdata) — `rafalmasiarek\ContactForm\Model`
- [`OutboundEmail`](#outboundemail) — `rafalmasiarek\ContactForm\Model`
- [`ArrayMessageResolver`](#arraymessageresolver) — `rafalmasiarek\ContactForm\Support`
- [`ContactDataValidator`](#contactdatavalidator) — `rafalmasiarek\ContactForm\Support`
- [`DefaultContactTemplate`](#defaultcontacttemplate) — `rafalmasiarek\ContactForm\Support`
- [`DefaultIpResolver`](#defaultipresolver) — `rafalmasiarek\ContactForm\Support`
- [`NullLogger`](#nulllogger) — `rafalmasiarek\ContactForm\Support`

### Interfaces

- [`AttemptLoggerInterface`](#attemptloggerinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`ContactFormHookInterface`](#contactformhookinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`EmailSenderInterface`](#emailsenderinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`EmailTemplateInterface`](#emailtemplateinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`ErrorCodeProviderInterface`](#errorcodeproviderinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`IpResolverInterface`](#ipresolverinterface) — `rafalmasiarek\ContactForm\Contracts`
- [`MessageResolverInterface`](#messageresolverinterface) — `rafalmasiarek\ContactForm\Contracts`

## `AttemptLoggerInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\AttemptLoggerInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/AttemptLoggerInterface.php#L12)

Minimal logging contract used by the service. You can adapt a PSR-3 logger by implementing this interface.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`info()`](#attemptloggerinterface-info) | public | `void` | Log an informational message. |
| [`warning()`](#attemptloggerinterface-warning) | public | `void` | Log a warning message. |
| [`error()`](#attemptloggerinterface-error) | public | `void` | Log an error message. |

<details>
<summary><code>info()</code></summary>

#### `AttemptLoggerInterface info()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/AttemptLoggerInterface.php#L20)

```php
public function info(string $message, array $context): void
```

Log an informational message.

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array<string, mixed>` | — |

</details>

<details>
<summary><code>warning()</code></summary>

#### `AttemptLoggerInterface warning()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/AttemptLoggerInterface.php#L28)

```php
public function warning(string $message, array $context): void
```

Log a warning message.

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array<string, mixed>` | — |

</details>

<details>
<summary><code>error()</code></summary>

#### `AttemptLoggerInterface error()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/AttemptLoggerInterface.php#L36)

```php
public function error(string $message, array $context): void
```

Log an error message.

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array<string, mixed>` | — |

</details>

## `ContactFormHookInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\ContactFormHookInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ContactFormHookInterface.php#L20)

Lifecycle hooks for the contact form pipeline. Hooks receive a read-write façade ({@see ContactDataHook}) that exposes the live {@see ContactData}. Use hooks to normalize/enrich the payload, add diagnostics, or perform side effects (metrics, auditing, etc.). IMPORTANT: - Hooks may mutate data via ContactDataHook. - Validators must operate on the read-only snapshot ({@see ContactDataValidator}).

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`onBeforeValidate()`](#contactformhookinterface-onbeforevalidate) | public | `void` | Invoked just before validators are executed. You may mutate the payload (e.g., normalize fields, enrich meta) or throw to abort. |
| [`onAfterValidate()`](#contactformhookinterface-onaftervalidate) | public | `void` | NEW: Invoked after all validators have succeeded, but before the email is rendered and sent. This is the right place to annotate body/meta based on validator outputs (available in $validatorsMeta) without repeating the validator's work. The $validatorsMeta bag is what the service collected under: $service->meta['validators'] (per-validator namespace) Example shape: [ 'csrfValidator' => ['status'=>'ok', ...], 'emailValidator' => ['status'=>'ok', ...], 'threatScan' => ['status'=>'pass', 'summary'=>{...}], ... ] |
| [`onAfterSend()`](#contactformhookinterface-onaftersend) | public | `void` | Invoked after a successful send. |
| [`onSendFailure()`](#contactformhookinterface-onsendfailure) | public | `void` | Invoked if the send operation fails with an exception. |

<details>
<summary><code>onBeforeValidate()</code></summary>

#### `ContactFormHookInterface onBeforeValidate()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ContactFormHookInterface.php#L31)

```php
public function onBeforeValidate(ContactDataHook $d): void
```

Invoked just before validators are executed. You may mutate the payload (e.g., normalize fields, enrich meta) or throw to abort.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactDataHook`](#contactdatahook) | Read-write access to the live ContactData. |

| Throws | When |
|---|---|
| `\Throwable` | To abort the pipeline with a custom reason. |

</details>

<details>
<summary><code>onAfterValidate()</code></summary>

#### `ContactFormHookInterface onAfterValidate()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ContactFormHookInterface.php#L54)

```php
public function onAfterValidate(ContactDataHook $d, array $validatorsMeta): void
```

NEW: Invoked after all validators have succeeded, but before the email is rendered and sent. This is the right place to annotate body/meta based on validator outputs (available in $validatorsMeta) without repeating the validator's work. The $validatorsMeta bag is what the service collected under: $service->meta['validators']  (per-validator namespace) Example shape: [ 'csrfValidator'  => ['status'=>'ok',   ...], 'emailValidator' => ['status'=>'ok',   ...], 'threatScan'     => ['status'=>'pass', 'summary'=>{...}], ... ]

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactDataHook`](#contactdatahook) | Read-write access to the live ContactData. |
| `$validatorsMeta` | `array<string, mixed>` | Aggregated meta produced by validators. |

</details>

<details>
<summary><code>onAfterSend()</code></summary>

#### `ContactFormHookInterface onAfterSend()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ContactFormHookInterface.php#L63)

```php
public function onAfterSend(ContactDataHook $d, string $messageId): void
```

Invoked after a successful send.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactDataHook`](#contactdatahook) | Read-write access to the live ContactData. |
| `$messageId` | `string` | Optional transport message id (if any). |

</details>

<details>
<summary><code>onSendFailure()</code></summary>

#### `ContactFormHookInterface onSendFailure()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ContactFormHookInterface.php#L72)

```php
public function onSendFailure(ContactDataHook $d, \Throwable $e): void
```

Invoked if the send operation fails with an exception.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactDataHook`](#contactdatahook) | Read-write access to the live ContactData. |
| `$e` | `\Throwable` | The failure exception. |

</details>

## `EmailSenderInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\EmailSenderInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/EmailSenderInterface.php#L14)

Contract for sending outbound emails. Implementations may use any transport (SMTP, API, sendmail, etc.).

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`send()`](#emailsenderinterface-send) | public | `bool` | Send an email message. Implementations should throw on transport/config errors. |

<details>
<summary><code>send()</code></summary>

#### `EmailSenderInterface send()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/EmailSenderInterface.php#L26)

```php
public function send(OutboundEmail $email): bool
```

Send an email message. Implementations should throw on transport/config errors.

| Param | Type | Description |
|---|---|---|
| `$email` | [`OutboundEmail`](#outboundemail) | The message to send. |

**Returns:** `bool` — True on success; false otherwise.

| Throws | When |
|---|---|
| `\Throwable` | On transport or configuration failure. |

</details>

## `EmailTemplateInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\EmailTemplateInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/EmailTemplateInterface.php#L17)

Contract for rendering email bodies from ContactData. Implementations are responsible for producing the HTML and plain-text bodies shown in outbound messages. They may freely use $d->meta to include additional context (IP, UA, referer, country, etc.) previously attached by hooks in the application layer.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`renderHtml()`](#emailtemplateinterface-renderhtml) | public | `string` | Render the HTML body for an outbound email. |
| [`renderText()`](#emailtemplateinterface-rendertext) | public | `string\|null` | Render the plain-text body for an outbound email. Return null to allow the caller to auto-fallback to a text version generated from the HTML body (e.g., via strip_tags()). |

<details>
<summary><code>renderHtml()</code></summary>

#### `EmailTemplateInterface renderHtml()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/EmailTemplateInterface.php#L25)

```php
public function renderHtml(ContactData $d): string
```

Render the HTML body for an outbound email.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Contact form data, including optional meta. |

**Returns:** `string` — HTML markup for the message body.

</details>

<details>
<summary><code>renderText()</code></summary>

#### `EmailTemplateInterface renderText()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/EmailTemplateInterface.php#L36)

```php
public function renderText(ContactData $d): ?string
```

Render the plain-text body for an outbound email. Return null to allow the caller to auto-fallback to a text version generated from the HTML body (e.g., via strip_tags()).

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Contact form data, including optional meta. |

**Returns:** `string\|null` — Text body or null to let caller fallback.

</details>

## `ErrorCodeProviderInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\ErrorCodeProviderInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ErrorCodeProviderInterface.php#L14)

Contracts for exceptions that carry a stable machine-readable error code. Implement this on your validators/hooks/transport exceptions so that the ContactFormService can return consistent: ['ok' => false, 'code' => 'ERR_FOO', 'message' => '...']

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`getErrorCode()`](#errorcodeproviderinterface-geterrorcode) | public | `string` | Return a stable symbolic code, e.g. "ERR_EMAIL_INVALID". |
| [`getMessageContext()`](#errorcodeproviderinterface-getmessagecontext) | public | `array<int\|string, mixed>` | Optional printf/vsprintf context passed to the message resolver. |

<details>
<summary><code>getErrorCode()</code></summary>

#### `ErrorCodeProviderInterface getErrorCode()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ErrorCodeProviderInterface.php#L19)

```php
public function getErrorCode(): string
```

Return a stable symbolic code, e.g. "ERR_EMAIL_INVALID".

**Returns:** `string`

</details>

<details>
<summary><code>getMessageContext()</code></summary>

#### `ErrorCodeProviderInterface getMessageContext()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/ErrorCodeProviderInterface.php#L26)

```php
public function getMessageContext(): array
```

Optional printf/vsprintf context passed to the message resolver.

**Returns:** `array<int\|string, mixed>` — Positional or named context values.

</details>

## `IpResolverInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\IpResolverInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/IpResolverInterface.php#L17)

Abstraction for resolving client IP and User-Agent from the current request. The default implementation reads from $_SERVER and is suitable for raw PHP without reverse proxies. Behind a proxy (Nginx, Cloudflare, load balancer) inject your own implementation that validates trusted proxy ranges before trusting forwarded headers.

**See:** \rafalmasiarek\ContactForm\Support\DefaultIpResolver

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`resolveClientIp()`](#ipresolverinterface-resolveclientip) | public | `string\|null` | Resolve the best-effort client IP address. |
| [`resolveUserAgent()`](#ipresolverinterface-resolveuseragent) | public | `string\|null` | Resolve the User-Agent header. |

<details>
<summary><code>resolveClientIp()</code></summary>

#### `IpResolverInterface resolveClientIp()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/IpResolverInterface.php#L24)

```php
public function resolveClientIp(): ?string
```

Resolve the best-effort client IP address.

**Returns:** `string\|null` — Client IP if available; otherwise null.

</details>

<details>
<summary><code>resolveUserAgent()</code></summary>

#### `IpResolverInterface resolveUserAgent()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/IpResolverInterface.php#L31)

```php
public function resolveUserAgent(): ?string
```

Resolve the User-Agent header.

**Returns:** `string\|null` — User-Agent if available; otherwise null.

</details>

## `MessageResolverInterface`

*interface* — `rafalmasiarek\ContactForm\Contracts\MessageResolverInterface` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/MessageResolverInterface.php#L12)

Resolves a human-friendly message and (optionally) structured metadata for a code. Implementations may support structured descriptors including HTTP/app error codes.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`resolve()`](#messageresolverinterface-resolve) | public | `string` | Resolve a UI-friendly message for a given code. |
| [`describe()`](#messageresolverinterface-describe) | public | `array{message: string, http?: int, errco?: int}` | Describe a code with optional metadata used by the API layer. Keys: - message (string) Human-readable message - http (int) Suggested HTTP status (API may default if missing) - errco (int) Numeric application error code |
| [`extend()`](#messageresolverinterface-extend) | public | `void` | Extend/override code mappings. Values may be either: - string → message only - array{message?:string\|int, msg?:string, errstr?:string, http?:int, status?:int, errco?:int, code?:int} (keys are normalized; errco/http/status/code are accepted) |

<details>
<summary><code>resolve()</code></summary>

#### `MessageResolverInterface resolve()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/MessageResolverInterface.php#L20)

```php
public function resolve(string $code, array $context): string
```

Resolve a UI-friendly message for a given code.

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | Stable symbolic code (OK_* / ERR_*). |
| `$context` | `array<int\|string, mixed>` | Optional vsprintf context. |

**Returns:** `string`

</details>

<details>
<summary><code>describe()</code></summary>

#### `MessageResolverInterface describe()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/MessageResolverInterface.php#L32)

```php
public function describe(string $code): array
```

Describe a code with optional metadata used by the API layer. Keys: - message (string)  Human-readable message - http    (int)     Suggested HTTP status (API may default if missing) - errco   (int)     Numeric application error code

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | — |

**Returns:** `array{message: string, http?: int, errco?: int}`

</details>

<details>
<summary><code>extend()</code></summary>

#### `MessageResolverInterface extend()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Contracts/MessageResolverInterface.php#L44)

```php
public function extend(array $map): void
```

Extend/override code mappings. Values may be either: - string → message only - array{message?:string|int, msg?:string, errstr?:string, http?:int, status?:int, errco?:int, code?:int} (keys are normalized; errco/http/status/code are accepted)

| Param | Type | Description |
|---|---|---|
| `$map` | `array<string, string\|array<string, mixed>>` | — |

</details>

## `Codes`

*final class* — `rafalmasiarek\ContactForm\Core\Codes` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/Codes.php#L12)

Centralized list of stable symbolic codes used by the core service. Transport-specific codes (e.g., SMTP) must live inside the transport implementation to avoid leaking implementation details globally.

## `ContactDataHook`

*final class* — `rafalmasiarek\ContactForm\Core\ContactDataHook` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactDataHook.php#L33)

Thin read-write façade around the live {@see ContactData} DTO plus a lightweight context bag for hooks. This wrapper intentionally exposes only a single accessor {@see data()} to keep the contract simple and explicit: hooks can freely read and modify the underlying mutable ContactData object. Hooks can mutate the DTO via data() and read request context (e.g. raw body) via body() / ctx(). Rationale: - Makes the "hook API surface" explicit (hooks operate on a wrapper). - Keeps validators separate (they should use a read-only snapshot). Example: <code> public function onBeforeValidate(ContactDataHook $d): void { $data = $d->data(); $data->name = trim($data->name); $data->meta['normalized'] = true; } </code>

**Depends on:** [`ContactData`](#contactdata)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$data` | [`ContactData`](#contactdata) | private | — |
| `$context` | `array<string, mixed>` | private | Arbitrary, read-only context for hooks (e.g. ["body"=>[...], "ip"=>...]). |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#contactdatahook-__construct) | public | — | — |
| [`data()`](#contactdatahook-data) | public | [`ContactData`](#contactdata) | Access the underlying mutable DTO. |
| [`body()`](#contactdatahook-body) | public | `array<mixed>` | Convenience: return the original parsed request body (if provided). |
| [`ctx()`](#contactdatahook-ctx) | public | `mixed` | Generic context accessor. |

<details>
<summary><code>__construct()</code></summary>

#### `ContactDataHook __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactDataHook.php#L48)

```php
public function __construct(ContactData $data, array $context)
```

| Param | Type | Description |
|---|---|---|
| `$data` | [`ContactData`](#contactdata) | — |
| `$context` | `array<string, mixed>` | Optional extras like ['body'=>array, ...] |

</details>

<details>
<summary><code>data()</code></summary>

#### `ContactDataHook data()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactDataHook.php#L57)

```php
public function data(): ContactData
```

Access the underlying mutable DTO.

**Returns:** [`ContactData`](#contactdata)

</details>

<details>
<summary><code>body()</code></summary>

#### `ContactDataHook body()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactDataHook.php#L67)

```php
public function body(): array
```

Convenience: return the original parsed request body (if provided).

**Returns:** `array<mixed>`

</details>

<details>
<summary><code>ctx()</code></summary>

#### `ContactDataHook ctx()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactDataHook.php#L80)

```php
public function ctx(string $key,  $default): 
```

Generic context accessor.

| Param | Type | Description |
|---|---|---|
| `$key` | `string` | — |
| `$default` | `mixed` | — |

**Returns:** `mixed`

</details>

## `ContactFormService`

*final class* — `rafalmasiarek\ContactForm\Core\ContactFormService` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L44)

Lightweight, pluggable contact form service. Responsibilities: - Runs user-provided validators (callables) that may throw on error. - Renders an HTML email from {@see ContactData}. - Sends the message via an injected {@see EmailSenderInterface} (or a lazy PHPMailer sender if SMTP config is provided). - Logs structured events using {@see AttemptLoggerInterface}. - Retrieves request metadata (IP/UA) via {@see IpResolverInterface}. Notes: - The service returns a simple array payload, not an HTTP response. - External checks (CSRF / reCAPTCHA / geoblocking) should happen upstream. - Message text is resolved from a symbolic code via {@see MessageResolverInterface}.

**Depends on:** [`AttemptLoggerInterface`](#attemptloggerinterface), [`EmailSenderInterface`](#emailsenderinterface), [`EmailTemplateInterface`](#emailtemplateinterface), [`MessageResolverInterface`](#messageresolverinterface), [`IpResolverInterface`](#ipresolverinterface)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$validators` | `array<string, callable(ContactDataValidator): (void\|array<string, mixed>)>` | private | List of validator callables executed before sending. Each validator receives ContactData and may throw to abort. |
| `$logger` | [`AttemptLoggerInterface`](#attemptloggerinterface) | private | Logger implementation (defaults to {@see NullLogger}). |
| `$context` | `array<string, mixed>` | private | — |
| `$hooks` | `ContactFormHookInterface[]` | private | Hooks executed around the send pipeline. Methods may optionally return arrays with meta which will be collected. |
| `$hooksBeforeGlobal` | `ContactFormHookInterface[]` | private | Hooks run globally before any validator |
| `$hooksBeforeByValidator` | `array<string, ContactFormHookInterface[]>` | private | Hooks run before specific validator label |
| `$hooksAfterGlobal` | `ContactFormHookInterface[]` | private | Hooks run once after all validators |
| `$hooksAfterByValidator` | `array<string, ContactFormHookInterface[]>` | private | Hooks run right after specific validator label |
| `$emailSender` | `EmailSenderInterface\|null` | private | Optional sender; falls back to PHPMailer sender if SMTP config is provided. |
| `$template` | `EmailTemplateInterface\|null` | private | Optional template used to generate the HTML and plain-text bodies. |
| `$smtpConfig` | `array<string, mixed>\|null` | private | SMTP configuration used to lazily construct a default PHPMailer sender when no custom {@see EmailSenderInterface} is injected. |
| `$messages` | [`MessageResolverInterface`](#messageresolverinterface) | private | Message resolver for code → text mapping. |
| `$ipResolver` | `IpResolverInterface\|null` | private | Optional IP/UA resolver used as fallback when client context is not provided via withContext(). |
| `$meta` | `array<string, mixed>` | private | Aggregated meta bag collected from validators, hooks and request data. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#contactformservice-__construct) | public | — | — |
| [`withRequestBody()`](#contactformservice-withrequestbody) | public | `$this` | Store the original parsed request body for hook access. |
| [`withContext()`](#contactformservice-withcontext) | public | `$this` | (Optional) Generic context setter to add client/server/request info. |
| [`withIpResolver()`](#contactformservice-withipresolver) | public | `$this` | Inject a custom IP/UA resolver used as fallback when client context is absent. If not set, {@see DefaultIpResolver} is used (reads from $_SERVER, no proxy headers). Behind a reverse proxy inject your own implementation that validates trusted ranges. The resolver is only consulted when 'client.ip' or 'client.ua' are missing from the context provided via {@see withContext()}. Explicit context always takes priority. |
| [`setValidators()`](#contactformservice-setvalidators) | public | `$this` | Replace the full validator set. Accepted forms: - ['label' => callable] - [callable, callable] (labels auto-derived) |
| [`addValidator()`](#contactformservice-addvalidator) | public | `$this` | Add a single validator callable (optionally with a fixed name). |
| [`setLogger()`](#contactformservice-setlogger) | public | `$this` | Set a custom logger. |
| [`setHook()`](#contactformservice-sethook) | public | `$this` | Backward-compat: set a single hook (overwrites existing). Prefer setHooks()/addHook() for multiple hooks. |
| [`setHooks()`](#contactformservice-sethooks) | public | `$this` | Replace all hooks at once (order preserved). Each item may be: - ContactFormHookInterface instance (global: before-all & after-all), - array spec: [ ContactFormHookInterface $hook, 'beforeValidator' => string\|string[]\|null, 'afterValidator' => string\|string[]\|null ] Empty array [] for before/after means "global" in that phase. |
| [`addHook()`](#contactformservice-addhook) | public | `$this` | Append a hook (executed after existing ones). |
| [`setEmailSender()`](#contactformservice-setemailsender) | public | `$this` | Inject a custom email sender implementation. |
| [`setSmtpConfig()`](#contactformservice-setsmtpconfig) | public | `$this` | Replace SMTP configuration (used only if no custom sender is injected). |
| [`setMessageResolver()`](#contactformservice-setmessageresolver) | public | `$this` | Inject a custom message resolver (for i18n / per-project copy). |
| [`process()`](#contactformservice-process) | public | `array{ok: bool, code: string, message: string, meta: array<string, mixed>}` | Execute the full submission pipeline. Return shape (intentionally minimal): - on success: ['ok' => true, 'code' => 'OK_SENT', 'message' => ..., 'meta' => {...}] - on error: ['ok' => false, 'code' => 'ERR_* / custom', 'message' => ..., 'meta' => {...}] Meta contains outputs from validators and hooks (if they return arrays), as well as basic request metadata (ip/ua) when available. |
| [`makeDefaultSender()`](#contactformservice-makedefaultsender) | private | `EmailSenderInterface\|null` | Lazily construct the default PHPMailer-based sender if SMTP config is present. |
| [`success()`](#contactformservice-success) | private | `array{ok: true, code: string, message: string, meta: array<string, mixed>}` | Build a success response envelope. |
| [`fail()`](#contactformservice-fail) | private | `array{ok: false, code: string, message: string, meta: array<string, mixed>}` | Build a failure response envelope. |
| [`callableLabel()`](#contactformservice-callablelabel) | private | `string` | Best-effort, human-readable label for a callable validator (for meta). |
| [`validatorKey()`](#contactformservice-validatorkey) | private | `string` | Derive a stable, human-friendly key for a validator callable. Examples: - [ClassName, 'method'] => "ClassName.method" with namespace stripped - Closure => "closure.<hash8>" - "function_name" string => "function_name" - fallback => "callable" Small cosmetics: - Strip leading "ContactForm" and trailing "Validator" to shorten common names. |
| [`recordValidatorMeta()`](#contactformservice-recordvalidatormeta) | private | `void` | Persist per-validator meta under meta['validators'][$key]. The stored shape is a flat associative array, e.g.: meta['validators']['csrf.validate'] = [ 'status' => 'ok'\|'fail', 'error_code' => '...', 'field' => '...', 'meta' => [...], ... (any extra fields emitted by the validator) ] |

<details>
<summary><code>__construct()</code></summary>

#### `ContactFormService __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L118)

```php
public function __construct(?array $smtpConfig)
```

| Param | Type | Description |
|---|---|---|
| `$smtpConfig` | `array<string, mixed>\|null` | Optional SMTP config for lazy PHPMailer sender. |

</details>

<details>
<summary><code>withRequestBody()</code></summary>

#### `ContactFormService withRequestBody()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L130)

```php
public function withRequestBody(array $body): self
```

Store the original parsed request body for hook access.

| Param | Type | Description |
|---|---|---|
| `$body` | `array` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>withContext()</code></summary>

#### `ContactFormService withContext()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L142)

```php
public function withContext(array $ctx): self
```

(Optional) Generic context setter to add client/server/request info.

| Param | Type | Description |
|---|---|---|
| `$ctx` | `array<string, mixed>` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>withIpResolver()</code></summary>

#### `ContactFormService withIpResolver()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L161)

```php
public function withIpResolver(IpResolverInterface $resolver): self
```

Inject a custom IP/UA resolver used as fallback when client context is absent. If not set, {@see DefaultIpResolver} is used (reads from $_SERVER, no proxy headers). Behind a reverse proxy inject your own implementation that validates trusted ranges. The resolver is only consulted when 'client.ip' or 'client.ua' are missing from the context provided via {@see withContext()}. Explicit context always takes priority.

| Param | Type | Description |
|---|---|---|
| `$resolver` | [`IpResolverInterface`](#ipresolverinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setValidators()</code></summary>

#### `ContactFormService setValidators()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L177)

```php
public function setValidators(array $validators): self
```

Replace the full validator set. Accepted forms: - ['label' => callable] - [callable, callable] (labels auto-derived)

| Param | Type | Description |
|---|---|---|
| `$validators` | `array<string\|int, callable>` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>addValidator()</code></summary>

#### `ContactFormService addValidator()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L197)

```php
public function addValidator(callable $validator, ?string $name): self
```

Add a single validator callable (optionally with a fixed name).

| Param | Type | Description |
|---|---|---|
| `$validator` | `callable(ContactDataValidator): (void\|array<string, mixed>)` | — |
| `$name` | `string\|null` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setLogger()</code></summary>

#### `ContactFormService setLogger()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L212)

```php
public function setLogger(AttemptLoggerInterface $logger): self
```

Set a custom logger.

| Param | Type | Description |
|---|---|---|
| `$logger` | [`AttemptLoggerInterface`](#attemptloggerinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setHook()</code></summary>

#### `ContactFormService setHook()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L225)

> **Deprecated** — Use setHooks() or addHook() instead.

```php
public function setHook(ContactFormHookInterface $hook): self
```

Backward-compat: set a single hook (overwrites existing). Prefer setHooks()/addHook() for multiple hooks.

| Param | Type | Description |
|---|---|---|
| `$hook` | [`ContactFormHookInterface`](#contactformhookinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setHooks()</code></summary>

#### `ContactFormService setHooks()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L243)

```php
public function setHooks(array $hooks): self
```

Replace all hooks at once (order preserved). Each item may be: - ContactFormHookInterface instance (global: before-all & after-all), - array spec: [ ContactFormHookInterface $hook, 'beforeValidator' => string|string[]|null, 'afterValidator' => string|string[]|null ] Empty array [] for before/after means "global" in that phase.

| Param | Type | Description |
|---|---|---|
| `$hooks` | `array<int, ContactFormHookInterface\|array>` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>addHook()</code></summary>

#### `ContactFormService addHook()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L314)

```php
public function addHook(ContactFormHookInterface $hook): self
```

Append a hook (executed after existing ones).

| Param | Type | Description |
|---|---|---|
| `$hook` | [`ContactFormHookInterface`](#contactformhookinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setEmailSender()</code></summary>

#### `ContactFormService setEmailSender()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L325)

```php
public function setEmailSender(EmailSenderInterface $sender): self
```

Inject a custom email sender implementation.

| Param | Type | Description |
|---|---|---|
| `$sender` | [`EmailSenderInterface`](#emailsenderinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setSmtpConfig()</code></summary>

#### `ContactFormService setSmtpConfig()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L337)

```php
public function setSmtpConfig(?array $smtp): self
```

Replace SMTP configuration (used only if no custom sender is injected).

| Param | Type | Description |
|---|---|---|
| `$smtp` | `array<string, mixed>\|null` | — |

**Returns:** `$this`

</details>

<details>
<summary><code>setMessageResolver()</code></summary>

#### `ContactFormService setMessageResolver()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L348)

```php
public function setMessageResolver(MessageResolverInterface $resolver): self
```

Inject a custom message resolver (for i18n / per-project copy).

| Param | Type | Description |
|---|---|---|
| `$resolver` | [`MessageResolverInterface`](#messageresolverinterface) | — |

**Returns:** `$this`

</details>

<details>
<summary><code>process()</code></summary>

#### `ContactFormService process()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L367)

```php
public function process(ContactData $data): array
```

Execute the full submission pipeline. Return shape (intentionally minimal): - on success: ['ok' => true,  'code' => 'OK_SENT',        'message' => ..., 'meta' => {...}] - on error:   ['ok' => false, 'code' => 'ERR_* / custom', 'message' => ..., 'meta' => {...}] Meta contains outputs from validators and hooks (if they return arrays), as well as basic request metadata (ip/ua) when available.

| Param | Type | Description |
|---|---|---|
| `$data` | [`ContactData`](#contactdata) | Submitted contact data. |

**Returns:** `array{ok: bool, code: string, message: string, meta: array<string, mixed>}`

</details>

<details>
<summary><code>makeDefaultSender()</code></summary>

#### `ContactFormService makeDefaultSender()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L554)

```php
private function makeDefaultSender(): ?EmailSenderInterface
```

Lazily construct the default PHPMailer-based sender if SMTP config is present.

**Returns:** `EmailSenderInterface\|null`

</details>

<details>
<summary><code>success()</code></summary>

#### `ContactFormService success()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L573)

```php
private function success(string $code, string $message, array $meta): array
```

Build a success response envelope.

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | Stable symbolic code, e.g. Codes::OK_SENT |
| `$message` | `string` | Human-friendly message resolved by the resolver. |
| `$meta` | `array<string, mixed>` | Aggregated meta to return to the caller. |

**Returns:** `array{ok: true, code: string, message: string, meta: array<string, mixed>}`

</details>

<details>
<summary><code>fail()</code></summary>

#### `ContactFormService fail()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L591)

```php
private function fail(string $code, string $message, array $meta): array
```

Build a failure response envelope.

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | Stable symbolic code, e.g. 'ERR_EMAIL_INVALID' or transport-specific (e.g. PhpMailerEmailSender::ERR_SMTP_AUTH). |
| `$message` | `string` | Human-friendly message resolved by the resolver. |
| `$meta` | `array<string, mixed>` | Aggregated meta to return to the caller. |

**Returns:** `array{ok: false, code: string, message: string, meta: array<string, mixed>}`

</details>

<details>
<summary><code>callableLabel()</code></summary>

#### `ContactFormService callableLabel()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L606)

```php
private function callableLabel(callable $cb): string
```

Best-effort, human-readable label for a callable validator (for meta).

| Param | Type | Description |
|---|---|---|
| `$cb` | `callable` | — |

**Returns:** `string`

</details>

<details>
<summary><code>validatorKey()</code></summary>

#### `ContactFormService validatorKey()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L637)

```php
private function validatorKey(callable $cb): string
```

Derive a stable, human-friendly key for a validator callable. Examples: - [ClassName, 'method']   => "ClassName.method" with namespace stripped - Closure                 => "closure.<hash8>" - "function_name" string  => "function_name" - fallback                => "callable" Small cosmetics: - Strip leading "ContactForm" and trailing "Validator" to shorten common names.

| Param | Type | Description |
|---|---|---|
| `$cb` | `callable` | — |

**Returns:** `string`

</details>

<details>
<summary><code>recordValidatorMeta()</code></summary>

#### `ContactFormService recordValidatorMeta()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ContactFormService.php#L675)

```php
private function recordValidatorMeta(callable $validator, array $meta): void
```

Persist per-validator meta under meta['validators'][$key]. The stored shape is a flat associative array, e.g.: meta['validators']['csrf.validate'] = [ 'status' => 'ok'|'fail', 'error_code' => '...', 'field' => '...', 'meta' => [...], ... (any extra fields emitted by the validator) ]

| Param | Type | Description |
|---|---|---|
| `$validator` | `callable` | The validator that produced meta. |
| `$meta` | `array<string, mixed>` | The metadata to store. |

</details>

## `ValidationException`

*final class* — `rafalmasiarek\ContactForm\Core\ValidationException` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ValidationException.php#L14)

Generic validation exception for contact form validators. - $code: short, machine-readable error code (e.g. EMAIL_INVALID, NAME_REQUIRED) - $field: optional field name related to the error (e.g. "email") - $meta: optional extra details (remain internal / for logging)

extends `\RuntimeException`

**Depends on:** `\Throwable`

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$errorCode` | `string` | private | — |
| `$field` | `string\|null` | private | — |
| `$meta` | `array<string, mixed>` | private | — |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#validationexception-__construct) | public | — | — |
| [`getErrorCode()`](#validationexception-geterrorcode) | public | `string` | Machine-readable error code (e.g., EMAIL_INVALID). |
| [`getField()`](#validationexception-getfield) | public | `?string` | Field name related to the validation error, if any. |
| [`getMeta()`](#validationexception-getmeta) | public | `array` | Extra details (for logging/diagnostics). |

<details>
<summary><code>__construct()</code></summary>

#### `ValidationException __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ValidationException.php#L33)

```php
public function __construct(string $errorCode, ?string $field, string $message, array $meta, int $code, ?\Throwable $previous)
```

| Param | Type | Description |
|---|---|---|
| `$errorCode` | `string` | Machine-readable code, e.g. 'EMAIL_INVALID' |
| `$field` | `string\|null` | Related field name, e.g. 'email' |
| `$message` | `string` | Optional human message (defaults to $errorCode) |
| `$meta` | `array<string, mixed>` | Optional extra details for logs/diagnostics |
| `$code` | `int` | Native exception code |
| `$previous` | `\Throwable\|null` | Previous exception |

</details>

<details>
<summary><code>getErrorCode()</code></summary>

#### `ValidationException getErrorCode()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ValidationException.php#L48)

```php
public function getErrorCode(): string
```

Machine-readable error code (e.g., EMAIL_INVALID).

**Returns:** `string`

</details>

<details>
<summary><code>getField()</code></summary>

#### `ValidationException getField()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ValidationException.php#L54)

```php
public function getField(): ?string
```

Field name related to the validation error, if any.

**Returns:** `?string`

</details>

<details>
<summary><code>getMeta()</code></summary>

#### `ValidationException getMeta()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Core/ValidationException.php#L60)

```php
public function getMeta(): array
```

Extra details (for logging/diagnostics).

**Returns:** `array`

</details>

## `NativeMailSender`

*final class* — `rafalmasiarek\ContactForm\Mail\NativeMailSender` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/NativeMailSender.php#L38)

Class NativeMailSender Lightweight email sender using PHP's native {@see mail()} function. When to use: - You have a working sendmail-compatible binary on the system (e.g., sendmail, msmtp, mhsendmail) and PHP is configured to use it. - Simple messages without attachments are sufficient. Limitations: - No attachment support (PHP's mail() requires manual MIME boundaries). - Requires proper sendmail_path in php.ini or container setup. Docker + MailHog tip: - Install msmtp in your PHP image and set: sendmail_path = "/usr/bin/msmtp -t" - Point msmtp to MailHog (host: mailhog, port: 1025). Example: $sender = new NativeMailSender( from: 'no-reply@example.test', fromName: 'ContactForm Demo', to: 'inbox@example.test', replyTo: null ); $messageId = $sender->send($outboundEmail);

implements [`EmailSenderInterface`](#emailsenderinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#nativemailsender-__construct) | public | — | — |
| [`send()`](#nativemailsender-send) | public | `bool` | Send the email using PHP's native mail(). Pulls subject and body from OutboundEmail via getters if available, otherwise falls back to public properties (subject, bodyHtml, bodyText, body). |
| [`encodeHeader()`](#nativemailsender-encodeheader) | private | `string` | Encode header value using RFC 2047 (UTF-8, Base64). |
| [`domainFromEmail()`](#nativemailsender-domainfromemail) | private | `string\|null` | Extract domain part from an email address. |

<details>
<summary><code>__construct()</code></summary>

#### `NativeMailSender __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/NativeMailSender.php#L46)

```php
public function __construct(string $from, string $fromName, string $to, ?string $replyTo)
```

| Param | Type | Description |
|---|---|---|
| `$from` | `string` | Envelope/sender email, used in "From" header. |
| `$fromName` | `string` | Human-readable sender name (RFC 2047 encoded). |
| `$to` | `string` | Primary recipient (comma-separated list supported by mail()). |
| `$replyTo` | `string\|null` | Optional Reply-To address (e.g., user email from a contact form). |

</details>

<details>
<summary><code>send()</code></summary>

#### `NativeMailSender send()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/NativeMailSender.php#L64)

```php
public function send(OutboundEmail $email): bool
```

Send the email using PHP's native mail(). Pulls subject and body from OutboundEmail via getters if available, otherwise falls back to public properties (subject, bodyHtml, bodyText, body).

| Param | Type | Description |
|---|---|---|
| `$email` | [`OutboundEmail`](#outboundemail) | — |

**Returns:** `bool` — True on success, false otherwise.

| Throws | When |
|---|---|
| `\RuntimeException` | If mail() returns false (misconfiguration or transport failure). |

</details>

<details>
<summary><code>encodeHeader()</code></summary>

#### `NativeMailSender encodeHeader()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/NativeMailSender.php#L119)

```php
private function encodeHeader(string $value): string
```

Encode header value using RFC 2047 (UTF-8, Base64).

| Param | Type | Description |
|---|---|---|
| `$value` | `string` | — |

**Returns:** `string` — Encoded header value safe for non-ASCII.

</details>

<details>
<summary><code>domainFromEmail()</code></summary>

#### `NativeMailSender domainFromEmail()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/NativeMailSender.php#L130)

```php
private function domainFromEmail(string $email): ?string
```

Extract domain part from an email address.

| Param | Type | Description |
|---|---|---|
| `$email` | `string` | — |

**Returns:** `string\|null` — Domain part or null if not available.

</details>

## `PhpMailerEmailSender`

*final class* — `rafalmasiarek\ContactForm\Mail\PhpMailerEmailSender` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L25)

Email sender implementation using PHPMailer with SMTP transport. All PHPMailer/SMTP-specific concerns live here: - local ERR_SMTP_* codes (as public constants), - mapping PHPMailer errors to those codes, - default human messages for those codes (registerDefaultMessages()). The service layer only relies on ErrorCodeProviderInterface; it never references SMTP symbols directly.

implements [`EmailSenderInterface`](#emailsenderinterface)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$smtp` | `array{host: string, username?: string, password?: string, port?: int, secure?: string, from: string, from_name?: string, to: string}` | private | SMTP configuration. Keys: - host (string) – SMTP host (required) - username (string) – SMTP username (optional) - password (string) – SMTP password (optional) - port (int) – SMTP port (default: 587) - secure (string) – 'tls' or 'ssl' (optional) - from (string) – From address (required) - from_name (string) – From name (optional) - to (string) – Recipient address (required) |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#phpmaileremailsender-__construct) | public | — | — |
| [`send()`](#phpmaileremailsender-send) | public | `bool` | Send the outbound email using PHPMailer and SMTP. |
| [`registerDefaultMessages()`](#phpmaileremailsender-registerdefaultmessages) | public static | `void` | Register default human-readable messages for this transport's ERR_SMTP_* codes. Call this once when wiring your resolver (you may still override any texts per project/locale). |
| [`mapSmtpError()`](#phpmaileremailsender-mapsmtperror) | private static | `array{code: string, ctx: array}` | Map PHPMailer's ErrorInfo/Exception message into a stable ERR_SMTP_* code. |

<details>
<summary><code>__construct()</code></summary>

#### `PhpMailerEmailSender __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L74)

```php
public function __construct(array $smtp)
```

| Param | Type | Description |
|---|---|---|
| `$smtp` | `array{host: string, username?: string, password?: string, port?: int, secure?: string, from: string, from_name?: string, to: string}` | — |

</details>

<details>
<summary><code>send()</code></summary>

#### `PhpMailerEmailSender send()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L89)

```php
public function send(OutboundEmail $email): bool
```

Send the outbound email using PHPMailer and SMTP.

| Param | Type | Description |
|---|---|---|
| `$email` | [`OutboundEmail`](#outboundemail) | Email message details. |

**Returns:** `bool` — True on success, false otherwise.

| Throws | When |
|---|---|
| `\RuntimeException` | If PHPMailer is not installed. |
| [`PhpMailerTransportException`](#phpmailertransportexception) | On SMTP transport failure with a stable ERR_SMTP_* code. |
| `\Throwable` | On non-SMTP configuration/runtime failures. |

</details>

<details>
<summary><code>registerDefaultMessages()</code></summary>

#### `PhpMailerEmailSender registerDefaultMessages()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L150)

```php
public static function registerDefaultMessages(ArrayMessageResolver $resolver): void
```

Register default human-readable messages for this transport's ERR_SMTP_* codes. Call this once when wiring your resolver (you may still override any texts per project/locale).

| Param | Type | Description |
|---|---|---|
| `$resolver` | [`ArrayMessageResolver`](#arraymessageresolver) | — |

</details>

<details>
<summary><code>mapSmtpError()</code></summary>

#### `PhpMailerEmailSender mapSmtpError()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L170)

```php
private static function mapSmtpError(string $errorInfo, string $exceptionMessage): array
```

Map PHPMailer's ErrorInfo/Exception message into a stable ERR_SMTP_* code.

| Param | Type | Description |
|---|---|---|
| `$errorInfo` | `string` | PHPMailer::$ErrorInfo (may be empty) |
| `$exceptionMessage` | `string` | Exception message from PHPMailer\Exception |

**Returns:** `array{code: string, ctx: array}`

</details>

## `PhpMailerTransportException`

*final class* — `rafalmasiarek\ContactForm\Mail\PhpMailerTransportException` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L203)

Local SMTP transport exception for PHPMailer sender. This exception stays private to the PHPMailer implementation file, implements ErrorCodeProvider and exposes a stable ERR_SMTP_* code.

implements [`ErrorCodeProviderInterface`](#errorcodeproviderinterface) · extends `\RuntimeException`

**Depends on:** `\Throwable`

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$ctx` | `array<int\|string, mixed>` | private | — |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#phpmailertransportexception-__construct) | public | — | — |
| [`getErrorCode()`](#phpmailertransportexception-geterrorcode) | public | `string` | — |
| [`getMessageContext()`](#phpmailertransportexception-getmessagecontext) | public | `array` | — |

<details>
<summary><code>__construct()</code></summary>

#### `PhpMailerTransportException __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L213)

```php
public function __construct(string $errorCode, array $ctx, ?\Throwable $prev)
```

| Param | Type | Description |
|---|---|---|
| `$errorCode` | `string` | Stable code (ERR_SMTP_*) |
| `$ctx` | `array<int\|string, mixed>` | Optional resolver context |
| `$prev` | `\Throwable\|null` | Previous exception |

</details>

<details>
<summary><code>getErrorCode()</code></summary>

#### `PhpMailerTransportException getErrorCode()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L221)

```php
public function getErrorCode(): string
```

**Returns:** `string`

</details>

<details>
<summary><code>getMessageContext()</code></summary>

#### `PhpMailerTransportException getMessageContext()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Mail/PhpMailerEmailSender.php#L227)

```php
public function getMessageContext(): array
```

**Returns:** `array`

</details>

## `ContactData`

*final class* — `rafalmasiarek\ContactForm\Model\ContactData` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Model/ContactData.php#L23)

Mutable DTO carrying contact form payload through the pipeline. Semantics: - Top-level properties (name, email, subject, message, phone) are common fields. - body: user-visible, safe-to-expose fields that may be rendered into the email. - meta: internal/diagnostic fields (CSRF, attemptId, ip, ua, flags, scan results, etc.). Templates should only render from top-level properties and `body`. `meta` MUST be considered internal and never rendered directly. All properties are intentionally public for simplicity in hooks and templates.

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$name` | `string` | public | — |
| `$email` | `string` | public | — |
| `$subject` | `string` | public | — |
| `$message` | `string` | public | — |
| `$phone` | `string` | public | — |
| `$body` | `array<string, scalar\|array\|object\|null>` | public | User-visible, safe-to-expose fields to be included in the email. Example: company, topic, orderId, preferredTime, etc. |
| `$meta` | `array<string, mixed>` | public | Internal fields used by the service, hooks, and validators. Example: csrf_token, attemptId, refId, ip, ua, threatScan, flags, attachments, etc. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#contactdata-__construct) | public | — | — |

<details>
<summary><code>__construct()</code></summary>

#### `ContactData __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Model/ContactData.php#L61)

```php
public function __construct(string $name, string $email, string $message, string $subject, string $phone, array $body, array $meta)
```

| Param | Type | Description |
|---|---|---|
| `$name` | `string` | — |
| `$email` | `string` | — |
| `$message` | `string` | — |
| `$subject` | `string` | — |
| `$phone` | `string` | — |
| `$body` | `array<string, scalar\|array\|object\|null>` | — |
| `$meta` | `array<string, mixed>` | — |

</details>

## `OutboundEmail`

*final class* — `rafalmasiarek\ContactForm\Model\OutboundEmail` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Model/OutboundEmail.php#L23)

Value object representing an outbound email ready to be sent. Instances are typically built via {@see OutboundEmail::fromContactData()}, which uses an {@see EmailTemplateInterface} to render bodies and maps basic address fields from SMTP config and ContactData. IMPORTANT: - Templates receive a **sanitized ContactData** (top-level fields + `body` only). - The internal `meta` bag from {@see ContactData} is **never** passed to templates or rendered into the message.

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#outboundemail-__construct) | public | — | — |
| [`fromContactData()`](#outboundemail-fromcontactdata) | public static | `self` | Factory that builds a ready-to-send email from ContactData using a template. Address resolution: - to: $smtp['to'] if present, otherwise $d->meta['deliver_to'] if present, otherwise empty string - from: $smtp['from'] if present, otherwise $d->email - reply-to: $d->email if present, otherwise same as "from" Templating: - The template receives a **sanitized ContactData** with `meta` cleared. - `body` remains available for user-visible extras. |

<details>
<summary><code>__construct()</code></summary>

#### `OutboundEmail __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Model/OutboundEmail.php#L34)

```php
public function __construct(string $to, string $from, string $replyTo, string $subject, string $htmlBody, ?string $textBody, array $attachments)
```

| Param | Type | Description |
|---|---|---|
| `$to` | `string` | Recipient email address. |
| `$from` | `string` | Sender email address (envelope/from header). |
| `$replyTo` | `string` | Reply-To email address. |
| `$subject` | `string` | Message subject. |
| `$htmlBody` | `string` | HTML body content. |
| `$textBody` | `string\|null` | Optional text body; null means "use fallback". |
| `$attachments` | `list<array{path: string, name?: string}>` | Files to attach. |

</details>

<details>
<summary><code>fromContactData()</code></summary>

#### `OutboundEmail fromContactData()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Model/OutboundEmail.php#L62)

```php
public static function fromContactData(ContactData $d, array $smtp, ?EmailTemplateInterface $tpl, array $attachments): self
```

Factory that builds a ready-to-send email from ContactData using a template. Address resolution: - to:       $smtp['to'] if present, otherwise $d->meta['deliver_to'] if present, otherwise empty string - from:     $smtp['from'] if present, otherwise $d->email - reply-to: $d->email if present, otherwise same as "from" Templating: - The template receives a **sanitized ContactData** with `meta` cleared. - `body` remains available for user-visible extras.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Contact form data. |
| `$smtp` | `array{to?: string, from?: string}` | Minimal SMTP mapping (only 'to' and 'from' are read here). |
| `$tpl` | `EmailTemplateInterface\|null` | Template to render bodies; default template if null. |
| `$attachments` | `list<array{path: string, name?: string}>` | Optional attachments. |

**Returns:** `self`

</details>

## `ArrayMessageResolver`

*final class* — `rafalmasiarek\ContactForm\Support\ArrayMessageResolver` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L28)

ArrayMessageResolver Lightweight message resolver with optional suggested HTTP status. Descriptor format (internal storage): [ 'message' => string,   // human-friendly text 'http'    => int?,     // optional suggested HTTP status ] Backward-compatible inputs accepted by extend()/constructor: - string                                 → treated as ['message' => <string>] - array with keys: * 'message' | 'msg' | 'errstr' | 'title'  → normalized to 'message' * 'http' | 'status'                      → normalized to 'http' * any 'errco' provided is IGNORED (we no longer store numeric app codes)

implements [`MessageResolverInterface`](#messageresolverinterface)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$map` | `array<string, array{message: string, http?: int}>` | private | — |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#arraymessageresolver-__construct) | public | — | — |
| [`resolve()`](#arraymessageresolver-resolve) | public | `string` | Resolve code to a human-friendly message. Unknown codes fall back to the unexpected-error message. |
| [`describe()`](#arraymessageresolver-describe) | public | `array{message: string, http?: int}` | Describe a code (message + optional suggested HTTP). Unknown codes fall back to the ERR_UNEXPECTED descriptor — never the raw code string, which would otherwise leak an internal identifier as if it were a human-readable message. |
| [`all()`](#arraymessageresolver-all) | public | `array<string, array{message: string, http?: int}>` | Return all registered descriptors keyed by error code. |
| [`extend()`](#arraymessageresolver-extend) | public | `void` | Merge descriptors into resolver (normalizing flexible input). |
| [`normalizeDescriptor()`](#arraymessageresolver-normalizedescriptor) | private | `array{message: string, http?: int}` | Normalize flexible input into a strict descriptor used internally. Accepted inputs: - string → ['message' => <string>] - array → keys normalized: * 'message' \| 'msg' \| 'errstr' \| 'title' → 'message' * 'http' \| 'status' → 'http' (int) * 'errco' is ignored (no longer stored) |

<details>
<summary><code>__construct()</code></summary>

#### `ArrayMessageResolver __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L41)

```php
public function __construct(array $map)
```

| Param | Type | Description |
|---|---|---|
| `$map` | `array<string, string\|array<string, mixed>>` | — |

</details>

<details>
<summary><code>resolve()</code></summary>

#### `ArrayMessageResolver resolve()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L65)

```php
public function resolve(string $code, array $context): string
```

Resolve code to a human-friendly message. Unknown codes fall back to the unexpected-error message.

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | — |
| `$context` | `array<int\|string>` | Values for vsprintf() placeholders (optional). |

**Returns:** `string`

</details>

<details>
<summary><code>describe()</code></summary>

#### `ArrayMessageResolver describe()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L80)

```php
public function describe(string $code): array
```

Describe a code (message + optional suggested HTTP). Unknown codes fall back to the ERR_UNEXPECTED descriptor — never the raw code string, which would otherwise leak an internal identifier as if it were a human-readable message.

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | — |

**Returns:** `array{message: string, http?: int}`

</details>

<details>
<summary><code>all()</code></summary>

#### `ArrayMessageResolver all()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L90)

```php
public function all(): array
```

Return all registered descriptors keyed by error code.

**Returns:** `array<string, array{message: string, http?: int}>`

</details>

<details>
<summary><code>extend()</code></summary>

#### `ArrayMessageResolver extend()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L101)

```php
public function extend(array $map): void
```

Merge descriptors into resolver (normalizing flexible input).

| Param | Type | Description |
|---|---|---|
| `$map` | `array<string, string\|array<string, mixed>>` | — |

</details>

<details>
<summary><code>normalizeDescriptor()</code></summary>

#### `ArrayMessageResolver normalizeDescriptor()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ArrayMessageResolver.php#L122)

```php
private function normalizeDescriptor(string $code, string|array $value): array
```

Normalize flexible input into a strict descriptor used internally. Accepted inputs: - string → ['message' => <string>] - array  → keys normalized: * 'message' | 'msg' | 'errstr' | 'title' → 'message' * 'http' | 'status'                      → 'http' (int) * 'errco' is ignored (no longer stored)

| Param | Type | Description |
|---|---|---|
| `$code` | `string` | — |
| `$value` | `string\|array<string, mixed>` | — |

**Returns:** `array{message: string, http?: int}`

</details>

## `ContactDataValidator`

*final class* — `rafalmasiarek\ContactForm\Support\ContactDataValidator` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L33)

Immutable, read-only snapshot view of {@see ContactData} for validators. This class is intended to be constructed via {@see ContactDataValidator::from()} immediately before running the validation pipeline. It captures the current state of the mutable {@see ContactData} into an immutable structure so that: - Hooks can still freely mutate the live {@see ContactData} object. - Validators operate on a stable, read-only snapshot, preventing accidental writes. Notes: - All scalar fields are copied to strings (empty string when missing). - {@see getMeta()} returns a defensive (deep) copy to avoid external mutation. - There are no setters by design; only accessor methods are exposed. Typical usage: <code> $snapshot = ContactDataValidator::from($contactData); $email    = $snapshot->getEmail(); // read-only </code>

**See:** ContactData

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$name` | `string` | private | Contact person's display name as provided by the submitter. Always a string; empty string when absent. |
| `$email` | `string` | private | Sender's email address (unvalidated raw input). Always a string; empty string when absent. |
| `$subject` | `string` | private | Message subject/title supplied by the user. Always a string; empty string when absent. |
| `$message` | `string` | private | Main message body supplied by the user. Always a string; empty string when absent. |
| `$phone` | `string` | private | Optional phone number supplied by the user (raw, unnormalized). Always a string; empty string when absent. |
| `$meta` | `array<string, mixed>` | private | Arbitrary metadata captured from {@see ContactData::$meta}. This is a deep-copied array to prevent external mutation of nested structures. |
| `$body` | `array<string, mixed>` | private | Arbitrary body captured from {@see ContactData::$body}. This is a deep-copied array to prevent external mutation of nested structures. |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`from()`](#contactdatavalidator-from) | public static | `self` | Create an immutable, read-only snapshot from the mutable {@see ContactData}. Each scalar field is normalized to a string (empty string when not set), and {@see ContactData::$meta} is deep-copied to ensure immutability. |
| [`deepCopyArray()`](#contactdatavalidator-deepcopyarray) | private static | `array<string, mixed>` | Recursively copy an array to produce a defensive clone. This prevents validators or downstream consumers from mutating nested metadata structures by reference. |
| [`getName()`](#contactdatavalidator-getname) | public | `string` | Get the submitter's name (read-only). |
| [`getEmail()`](#contactdatavalidator-getemail) | public | `string` | Get the submitter's email address (read-only, unvalidated). |
| [`getSubject()`](#contactdatavalidator-getsubject) | public | `string` | Get the message subject/title (read-only). |
| [`getMessage()`](#contactdatavalidator-getmessage) | public | `string` | Get the message body (read-only). |
| [`getPhone()`](#contactdatavalidator-getphone) | public | `string` | Get the phone number (read-only, unnormalized). |
| [`getMeta()`](#contactdatavalidator-getmeta) | public | `array<string, mixed>` | Get a defensive (deep) copy of the metadata bag. Modifications to the returned array will NOT affect the internal snapshot. |
| [`getBody()`](#contactdatavalidator-getbody) | public | `array<string, mixed>` | Get a defensive (deep) copy of the body bag. Modifications to the returned array will NOT affect the internal snapshot. |

<details>
<summary><code>from()</code></summary>

#### `ContactDataValidator from()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L108)

```php
public static function from(ContactData $d): self
```

Create an immutable, read-only snapshot from the mutable {@see ContactData}. Each scalar field is normalized to a string (empty string when not set), and {@see ContactData::$meta} is deep-copied to ensure immutability.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Mutable DTO to snapshot. |

**Returns:** `self` — Read-only snapshot for validators.

</details>

<details>
<summary><code>deepCopyArray()</code></summary>

#### `ContactDataValidator deepCopyArray()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L130)

```php
private static function deepCopyArray(array $a): array
```

Recursively copy an array to produce a defensive clone. This prevents validators or downstream consumers from mutating nested metadata structures by reference.

| Param | Type | Description |
|---|---|---|
| `$a` | `array<string, mixed>` | Source array. |

**Returns:** `array<string, mixed>` — Deep copy of the source array.

</details>

<details>
<summary><code>getName()</code></summary>

#### `ContactDataValidator getName()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L144)

```php
public function getName(): string
```

Get the submitter's name (read-only).

**Returns:** `string` — Non-null string (may be empty).

</details>

<details>
<summary><code>getEmail()</code></summary>

#### `ContactDataValidator getEmail()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L154)

```php
public function getEmail(): string
```

Get the submitter's email address (read-only, unvalidated).

**Returns:** `string` — Non-null string (may be empty).

</details>

<details>
<summary><code>getSubject()</code></summary>

#### `ContactDataValidator getSubject()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L164)

```php
public function getSubject(): string
```

Get the message subject/title (read-only).

**Returns:** `string` — Non-null string (may be empty).

</details>

<details>
<summary><code>getMessage()</code></summary>

#### `ContactDataValidator getMessage()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L174)

```php
public function getMessage(): string
```

Get the message body (read-only).

**Returns:** `string` — Non-null string (may be empty).

</details>

<details>
<summary><code>getPhone()</code></summary>

#### `ContactDataValidator getPhone()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L184)

```php
public function getPhone(): string
```

Get the phone number (read-only, unnormalized).

**Returns:** `string` — Non-null string (may be empty).

</details>

<details>
<summary><code>getMeta()</code></summary>

#### `ContactDataValidator getMeta()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L196)

```php
public function getMeta(): array
```

Get a defensive (deep) copy of the metadata bag. Modifications to the returned array will NOT affect the internal snapshot.

**Returns:** `array<string, mixed>` — Deep-copied metadata.

</details>

<details>
<summary><code>getBody()</code></summary>

#### `ContactDataValidator getBody()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/ContactDataValidator.php#L208)

```php
public function getBody(): array
```

Get a defensive (deep) copy of the body bag. Modifications to the returned array will NOT affect the internal snapshot.

**Returns:** `array<string, mixed>` — Deep-copied metadata.

</details>

## `DefaultContactTemplate`

*final class* — `rafalmasiarek\ContactForm\Support\DefaultContactTemplate` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultContactTemplate.php#L23)

Default email template used when no custom template is provided. Rendering rules for extra fields: - We render **only** from $d->body (user-visible extras). $d->meta is never rendered. Channel-aware values in $d->body: - If a value is an array with channels: * ['html'] — non-empty string → render label then inject raw HTML block. * ['text'] — non-empty string → render label then print escaped text (<pre> in HTML; plain in TEXT). - If a value is a scalar → render as a simple key/value pair with escaping. - Any other shapes are skipped silently to avoid noisy output.

implements [`EmailTemplateInterface`](#emailtemplateinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`renderHtml()`](#defaultcontacttemplate-renderhtml) | public | `string` | Render the HTML body for the contact message. |
| [`renderText()`](#defaultcontacttemplate-rendertext) | public | `string\|null` | Render the plain-text alternative for the contact message. |

<details>
<summary><code>renderHtml()</code></summary>

#### `DefaultContactTemplate renderHtml()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultContactTemplate.php#L31)

```php
public function renderHtml(ContactData $d): string
```

Render the HTML body for the contact message.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Contact data to render (sanitized by the caller; meta is not used here). |

**Returns:** `string` — HTML content.

</details>

<details>
<summary><code>renderText()</code></summary>

#### `DefaultContactTemplate renderText()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultContactTemplate.php#L93)

```php
public function renderText(ContactData $d): ?string
```

Render the plain-text alternative for the contact message.

| Param | Type | Description |
|---|---|---|
| `$d` | [`ContactData`](#contactdata) | Contact data to render (sanitized by the caller; meta is not used here). |

**Returns:** `string\|null` — Text content; null to omit the text part (we always return a string here).

</details>

## `DefaultIpResolver`

*final class* — `rafalmasiarek\ContactForm\Support\DefaultIpResolver` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultIpResolver.php#L18)

Default resolver that reads client IP and User-Agent directly from $_SERVER. Uses only REMOTE_ADDR — does not trust X-Forwarded-For or similar headers. Suitable for raw PHP deployments without a reverse proxy in front. Behind a proxy (Nginx, Cloudflare, load balancer) inject your own implementation that validates trusted proxy ranges before trusting forwarded headers.

implements [`IpResolverInterface`](#ipresolverinterface)

| Property | Type | Visibility | Description |
|---|---|---|---|
| `$server` | `array<string, mixed>` | private | — |

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`__construct()`](#defaultipresolver-__construct) | public | — | — |
| [`resolveClientIp()`](#defaultipresolver-resolveclientip) | public | `?string` | — |
| [`resolveUserAgent()`](#defaultipresolver-resolveuseragent) | public | `?string` | — |

<details>
<summary><code>__construct()</code></summary>

#### `DefaultIpResolver __construct()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultIpResolver.php#L28)

```php
public function __construct(array $server)
```

| Param | Type | Description |
|---|---|---|
| `$server` | `array<string, mixed>` | Optional server params. Defaults to $_SERVER. |

</details>

<details>
<summary><code>resolveClientIp()</code></summary>

#### `DefaultIpResolver resolveClientIp()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultIpResolver.php#L36)

```php
public function resolveClientIp(): ?string
```

**Returns:** `?string`

</details>

<details>
<summary><code>resolveUserAgent()</code></summary>

#### `DefaultIpResolver resolveUserAgent()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/DefaultIpResolver.php#L46)

```php
public function resolveUserAgent(): ?string
```

**Returns:** `?string`

</details>

## `NullLogger`

*final class* — `rafalmasiarek\ContactForm\Support\NullLogger` · [source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/NullLogger.php#L14)

No-op logger implementation. Useful as a safe default when you do not need logging.

implements [`AttemptLoggerInterface`](#attemptloggerinterface)

| Method | Visibility | Returns | Summary |
|---|---|---|---|
| [`info()`](#nulllogger-info) | public | `void` | — |
| [`warning()`](#nulllogger-warning) | public | `void` | — |
| [`error()`](#nulllogger-error) | public | `void` | — |

<details>
<summary><code>info()</code></summary>

#### `NullLogger info()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/NullLogger.php#L17)

```php
public function info(string $message, array $context): void
```

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array` | — |

</details>

<details>
<summary><code>warning()</code></summary>

#### `NullLogger warning()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/NullLogger.php#L20)

```php
public function warning(string $message, array $context): void
```

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array` | — |

</details>

<details>
<summary><code>error()</code></summary>

#### `NullLogger error()`

[source](https://github.com/rafalmasiarek/php-contact-form/blob/main/src/Support/NullLogger.php#L23)

```php
public function error(string $message, array $context): void
```

| Param | Type | Description |
|---|---|---|
| `$message` | `string` | — |
| `$context` | `array` | — |

</details>

