# ContactForm (PSR‑7 friendly)

Lightweight, framework-agnostic contact form service for PHP. It gives you a tiny, pluggable pipeline (hooks + validators), a simple DTO for requests, and transport-agnostic email sending (PHPMailer SMTP or native mail()), with example wiring for MailHog. Works with Composer or a standalone autoloader.

## Highlights

- **Small & decoupled**: no hard HTTP coupling; returns a simple array you can turn into any response.
- **Pluggable pipeline**: run validators (as callables) and hooks (before/after validation, after send, on failure).
- **Clean data model**: ContactData DTO + OutboundEmail builder.
- **Transports**: PHPMailer adapter (SMTP) or native mail() sender.
- **i18n-friendly messages**: resolve human texts via codes with ArrayMessageResolver.
- **PSR-7 friendly**: easy to slot into Slim, Laminas, etc.
- **Batteries included examples**: demo app with MailHog Docker, simple math CAPTCHA (hook + validator), required fields + email validators, IP annotation hook.

> Namespace: `rafalmasiarek\ContactForm` (PSR‑4 autoload).  
> PHP: **^8.0**

## Installation

```bash
composer require rafalmasiarek/contact-form
# or use a local path repo during development
```

If you use the PHPMailer adapter:
```bash
composer require phpmailer/phpmailer:^6.9
```

## Quickstart
```
git clone https://github.com/rafalmasiarek/php-contact-form.git php-contactform
cd php-contactform/examples
docker compose up -d
# open http://localhost:8080
```

## Using with composer
The repo ships a PSR-4 style autoload.php. Point it at src/ (and optional lib/ folders for hooks/validators) and require it in your front controller.
```
require __DIR__ . '/../autoload.php'; // or examples/lib/contactform/autoload.php
```

## Example wiring (simplified)
```php
$cfg = require __DIR__ . '/../config.php';

$resolver = new ArrayMessageResolver([
  'OK_SENT' => ['message' => 'Message sent', 'http' => 200],
  'ERR_VALIDATION' => ['message' => 'Validation error', 'http' => 422],
  'ERR_SEND_FAILED' => ['message' => 'Message could not be sent.', 'http' => 500],
]);

// optional: register default messages for custom validators
ContactForm\Validators\MathCaptchaValidator::registerDefaultMessages($resolver);

$service = (new ContactFormService())
  ->setMessageResolver($resolver)
  ->setHooks([
    new ContactForm\Hook\AnnotateIpHook(),
    new ContactForm\Hook\MathCaptchaHook(
      field: $cfg['captcha']['field'],
      metaKey: $cfg['captcha']['meta_key']
    ),
  ])
  ->setValidators([
    'required' => ContactForm\Validators\FieldsValidator::required(['name','email','message']),
    'email'    => ContactForm\Validators\FieldsValidator::email('email', 'strict'),
    'captcha'  => ContactForm\Validators\MathCaptchaValidator::validate(
      field: $cfg['captcha']['field'],
      sessionKey: $cfg['captcha']['session_key'],
      oneShot: (bool)$cfg['captcha']['one_shot']
    ),
  ]);

// Pick a sender: PHPMailer if available, otherwise native mail()
$sender = class_exists(\PHPMailer\PHPMailer\PHPMailer::class)
  ? new \rafalmasiarek\ContactForm\Mail\PhpMailerEmailSender($cfg['smtp'])
  : new \rafalmasiarek\ContactForm\Mail\NativeMailSender(
      from: $cfg['smtp']['from'],
      fromName: $cfg['smtp']['from_name'],
      to: $cfg['smtp']['to'],
      replyTo: null
    );
$service->setEmailSender($sender);

// Build data and process
$data = new \rafalmasiarek\ContactForm\Model\ContactData(
  name: $_POST['name'] ?? '',
  email: $_POST['email'] ?? '',
  message: $_POST['message'] ?? '',
  subject: $_POST['subject'] ?? '',
  phone: $_POST['phone'] ?? '',
  meta: [] 
);

echo json_encode($service->process($data));
```

## PSR‑7 / Middlewares

Add a middleware in your app that attaches `client` to request attributes and pass it via `withContext()`.

## Hooks

Implement `ContactFormHook` with any of these optional methods:
- `onBeforeValidate(ContactDataHook $dto)`
- `onAfterValidate(ContactDataHook $dto, string $validatorLabel)`
- `onBeforeSend(ContactDataHook $dto)`
- `onAfterSend(ContactDataHook $dto, string $transportMessageId)`

All hook errors are swallowed by default to protect the main flow.

## Email

Use `EmailSenderInterface` abstraction; provided adapters:
- `PhpMailerEmailSenderInterface` (depends on `phpmailer/phpmailer`)

`OutboundEmail` encapsulates the rendered message.

## Messages

`MessageResolverInterface` decouples symbolic codes from UI strings.  
`ArrayMessageResolver` is a simple in‑memory map with optional HTTP codes.

## Validation

Any callable validator is accepted. Library provides helper DTO `ContactDataValidator` to keep the code tidy.

## Versioning & BC

- Namespace is stable: `rafalmasiarek\ContactForm`.
- All classes are PSR‑4 autoloaded from `/src`.
- `DefaultIpResolverInterface` keeps a backward‑compatible constructor; prefer `Psr7IpResolverInterface` in PSR‑7 apps.

## License

MIT
