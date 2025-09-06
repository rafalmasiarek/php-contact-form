<?php
declare(strict_types=1);

/**
 * Lightweight PSR-4 autoloader for rafalmasiarek\ContactForm\*
 * Use when Composer is not available.
 *
 * Directory layout expected:
 *   /path/to/contactform/
 *     autoload.php      <-- this file
 *     src/
 *       Core/
 *       Contracts/
 *       Model/
 *       Support/
 *       Mail/
 */

// Namespace prefix and base directory
$__cf_prefix  = 'rafalmasiarek\\ContactForm\\';
$__cf_baseDir = __DIR__ . '/src/';

// Optional: allow overriding base dir via env/constant if you really need to
if (defined('CONTACTFORM_SRC_DIR') && is_string(CONTACTFORM_SRC_DIR)) {
    $__cf_baseDir = rtrim(CONTACTFORM_SRC_DIR, '/\\') . DIRECTORY_SEPARATOR;
} elseif ($env = getenv('CONTACTFORM_SRC_DIR')) {
    $__cf_baseDir = rtrim((string)$env, '/\\') . DIRECTORY_SEPARATOR;
}

// Small classmap for fastest path (optional but handy)
$__cf_classmap = [
    // Core
    'rafalmasiarek\\ContactForm\\Core\\ContactFormService'   => 'Core/ContactFormService.php',
    'rafalmasiarek\\ContactForm\\Core\\Codes'                => 'Core/Codes.php',
    'rafalmasiarek\\ContactForm\\Core\\ValidationException'  => 'Core/ValidationException.php',
    'rafalmasiarek\\ContactForm\\Core\\ContactDataHook'      => 'Core/ContactDataHook.php',

    // Contracts (interfaces)
    'rafalmasiarek\\ContactForm\\Contracts\\MessageResolverInterface'    => 'Contracts/MessageResolverInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\ContactFormHookInterface'    => 'Contracts/ContactFormHookInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\EmailSenderInterface'        => 'Contracts/EmailSenderInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\EmailTemplateInterface'      => 'Contracts/EmailTemplateInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\AttemptLoggerInterface'      => 'Contracts/AttemptLoggerInterface.php',

    // Model
    'rafalmasiarek\\ContactForm\\Model\\ContactData'         => 'Model/ContactData.php',
    'rafalmasiarek\\ContactForm\\Model\\OutboundEmail'       => 'Model/OutboundEmail.php',

    // Support
    'rafalmasiarek\\ContactForm\\Support\\ArrayMessageResolver'  => 'Support/ArrayMessageResolver.php',
    'rafalmasiarek\\ContactForm\\Support\\ContactDataValidator'  => 'Support/ContactDataValidator.php',
    'rafalmasiarek\\ContactForm\\Support\\NullLogger'            => 'Support/NullLogger.php',

    // Mail
    'rafalmasiarek\\ContactForm\\Mail\\PhpMailerEmailSender'     => 'Mail/PhpMailerEmailSender.php',
];

// Register autoloader
spl_autoload_register(static function (string $class) use ($__cf_prefix, $__cf_baseDir, $__cf_classmap): void {
    // Only handle our namespace
    if (strncmp($class, $__cf_prefix, strlen($__cf_prefix)) !== 0) {
        return;
    }

    // Classmap fast path
    if (isset($__cf_classmap[$class])) {
        $file = $__cf_baseDir . $__cf_classmap[$class];
        if (is_file($file)) {
            require $file;
        }
        return;
    }

    // PSR-4 fallback: prefix → baseDir, rest → path
    $relative = substr($class, strlen($__cf_prefix));
    $file = $__cf_baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (is_file($file)) {
        require $file;
        return;
    }

    // Optional: second fallback for case-mismatch on case-insensitive FS
    // (normally unnecessary if your tree matches namespaces exactly)
    $lower = $__cf_baseDir . strtolower(str_replace('\\', DIRECTORY_SEPARATOR, $relative)) . '.php';
    if (is_file($lower)) {
        require $lower;
    }
});

// (Optional) Try to include PHPMailer if present nearby (when using PhpMailerEmailSender without Composer)
$__try_phPMailer = static function(): void {
    $candidates = [
        __DIR__ . '/vendor/autoload.php',                 // local vendor
        dirname(__DIR__) . '/vendor/autoload.php',        // parent vendor
    ];
    foreach ($candidates as $cand) {
        if (is_file($cand)) {
            @require_once $cand;
            break;
        }
    }
};
$__try_phPMailer();
