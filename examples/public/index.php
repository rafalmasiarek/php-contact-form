<?php
session_start();

/**
 * Example front controller for ContactForm demo.
 *
 * - Uses a simple router to handle:
 *     POST /send  → processes the contact form and returns JSON
 *     GET  /      → serves a minimal HTML form
 * - Loads the library via ../bootstrap.php (tries Composer or project autoloader).
 * - Prefers PHPMailer-based sender; falls back to native mail() if not available.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../lib/contactform/Hooks/AnnotateIpHook.php';
require __DIR__ . '/../lib/contactform/Validators/FieldsValidator.php';

require __DIR__ . '/../lib/contactform/Hooks/MathCaptchaHook.php';
require __DIR__ . '/../lib/contactform/Validators/MathCaptchaValidator.php';

use rafalmasiarek\ContactForm\Core\ContactFormService;
use rafalmasiarek\ContactForm\Support\ArrayMessageResolver;
use rafalmasiarek\ContactForm\Model\ContactData;
use rafalmasiarek\ContactForm\Contracts\EmailSenderInterface;
use rafalmasiarek\ContactForm\Mail\PhpMailerEmailSender;
use rafalmasiarek\ContactForm\Mail\NativeMailSender;

use ContactForm\Hook\AnnotateIpHook;
use ContactForm\Hook\MathCaptchaHook;
use ContactForm\Validators\FieldsValidator;
use ContactForm\Validators\MathCaptchaValidator;


/** @var array{
 *   smtp: array{
 *     host:string, port:int, username:string, password:string, secure:string,
 *     from:string, from_name:string, to:string
 *   }
 * } $cfg
 */
$cfg = require __DIR__ . '/../config.php';

/**
 * Build an EmailSender using PHPMailer if available, otherwise fall back to native mail().
 *
 * The function prefers SMTP via PHPMailer (richer features, better deliverability)
 * and uses PHP's native mail() only if PHPMailer is not present. The native path
 * requires a properly configured sendmail-compatible binary (sendmail/msmtp/mhsendmail).
 *
 * @param array{
 *   host:string,
 *   port:int,
 *   username:string,
 *   password:string,
 *   secure:string,
 *   from:string,
 *   from_name:string,
 *   to:string
 * } $smtp  SMTP settings and addressing configuration.
 *
 * @return EmailSenderInterface  A concrete sender instance ready to inject into the service.
 *
 * @throws \RuntimeException If neither PHPMailer nor native mail() sender is available.
 */
function buildEmailSender(array $smtp): EmailSenderInterface
{
  // 1) Preferred: PHPMailer adapter (SMTP)
  if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    /** @var EmailSenderInterface $sender */
    $sender = new PhpMailerEmailSender($smtp);
    return $sender;
  }

  // 2) Fallback: native mail() (requires working sendmail_path)
  if (class_exists(NativeMailSender::class) && function_exists('mail')) {
    /** @var EmailSenderInterface $sender */
    $sender = new NativeMailSender(
      from: (string)$smtp['from'],
      fromName: (string)$smtp['from_name'],
      to: (string)$smtp['to'],
      replyTo: null // you may inject user's email here if desired
    );
    return $sender;
  }

  throw new \RuntimeException(
    'No email sender available: PhpMailerEmailSender class missing and native mail() not usable.'
  );
}

// ----------------------------------------------------------------------------
// Simple router
// ----------------------------------------------------------------------------
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if (!($uri === '/send' && $method === 'POST')) {
  $question = MathCaptchaHook::generateChallenge(
    (string)$cfg['captcha']['session_key']
  );
}

if ($uri === '/send' && $method === 'POST') {

  // Build message resolver (codes → message/http)
  $resolver = new ArrayMessageResolver([
    'OK_SENT'        => ['message' => 'Message sent',       'http' => 200],
    'ERR_VALIDATION' => ['message' => 'Validation error',   'http' => 422],
    'ERR_SEND'       => ['message' => 'Failed to send',     'http' => 500],
  ]);
  FieldsValidator::registerDefaultMessages($resolver);
  MathCaptchaValidator::registerDefaultMessages($resolver);


  // Build service
  $svc = (new ContactFormService())
    ->setMessageResolver($resolver)
    ->setValidators([
      'captcha'  => MathCaptchaValidator::validate(
        field: (string)$cfg['captcha']['field'],
        sessionKey: (string)$cfg['captcha']['session_key'],
        oneShot: (bool)$cfg['captcha']['one_shot']
      ),
      'validate:requiredFields' => FieldsValidator::required(['name', 'email', 'message']),
      'validate:email'    => FieldsValidator::email('email', 'strict'),
    ])
    ->setHooks([
      new AnnotateIpHook(),
      new MathCaptchaHook(
        field: (string)$cfg['captcha']['field'],
        metaKey: (string)$cfg['captcha']['meta_key']
      ),
    ]);

  // Configure EmailSender (PHPMailer preferred, fallback to native mail())
  try {
    $sender = buildEmailSender($cfg['smtp']);
    $svc->setEmailSender($sender);
  } catch (\Throwable $e) {
    // If neither sender is available, continue without a sender.
    // The service may return an error on send(), depending on implementation.
  }

  // Collect form payload
  $body = $_POST ?? [];
  $data = new ContactData(
    name: (string)($body['name']    ?? ''),
    email: (string)($body['email']   ?? ''),
    message: (string)($body['message'] ?? ''),
    subject: (string)($body['subject'] ?? ''),
    phone: (string)($body['phone']   ?? ''),
    meta: []
  );

  // Process and respond as JSON
  $res = $svc->process($data);

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($res, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// ----------------------------------------------------------------------------
// GET / → render minimal HTML page
// ----------------------------------------------------------------------------
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ContactForm Demo</title>
  <link rel="stylesheet" href="/style.css" />
</head>

<body>
  <main class="container">
    <h1>ContactForm Demo</h1>
    <p>MailHog UI: <a href="http://localhost:8025" target="_blank" rel="noopener">localhost:8025</a></p>

    <form id="contactForm" method="post" action="/send">
      <div class="row">
        <label>Name</label>
        <input type="text" name="name" required />
      </div>
      <div class="row">
        <label>Email</label>
        <input type="email" name="email" required />
      </div>
      <div class="row">
        <label>Subject</label>
        <input type="text" name="subject" />
      </div>
      <div class="row">
        <label>Message</label>
        <textarea name="message" rows="5" required></textarea>
      </div>
      <div class="row">
        <label>
          Prove you are human:
          <strong><?= htmlspecialchars($question, ENT_QUOTES, 'UTF-8') ?></strong>
        </label>
        <input type="text" name="<?= htmlspecialchars($cfg['captcha']['field'], ENT_QUOTES, 'UTF-8') ?>" inputmode="numeric" pattern="[0-9\-]*" required />
      </div>
      <div class="row">
        <label>Phone (optional)</label>
        <input type="text" name="phone" />
      </div>
      <button type="submit">Send</button>
    </form>

    <pre id="out"></pre>
  </main>

  <script>
    const form = document.getElementById('contactForm');
    const out = document.getElementById('out');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const data = new FormData(form);
      const res = await fetch('/send', {
        method: 'POST',
        body: data
      });
      const json = await res.json();
      out.textContent = JSON.stringify(json, null, 2);
      if (json.ok) alert('Sent! Check MailHog at :8025');
    });
  </script>
</body>

</html>