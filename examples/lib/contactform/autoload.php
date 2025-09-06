<?php
declare(strict_types=1);

$__cf_prefix  = 'rafalmasiarek\\ContactForm\\';
$__cf_baseDir = __DIR__ . '/src/';

if (defined('CONTACTFORM_SRC_DIR') && is_string(CONTACTFORM_SRC_DIR)) {
    $__cf_baseDir = rtrim(CONTACTFORM_SRC_DIR, '/\\') . DIRECTORY_SEPARATOR;
} elseif ($env = getenv('CONTACTFORM_SRC_DIR')) {
    $__cf_baseDir = rtrim((string)$env, '/\\') . DIRECTORY_SEPARATOR;
}

$__cf_classmap = [
    'rafalmasiarek\\ContactForm\\Core\\ContactFormService'   => 'Core/ContactFormService.php',
    'rafalmasiarek\\ContactForm\\Core\\Codes'                => 'Core/Codes.php',
    'rafalmasiarek\\ContactForm\\Core\\ValidationException'  => 'Core/ValidationException.php',
    'rafalmasiarek\\ContactForm\\Core\\ContactDataHook'      => 'Core/ContactDataHook.php',

    'rafalmasiarek\\ContactForm\\Contracts\\MessageResolverInterface'    => 'Contracts/MessageResolverInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\ContactFormHookInterface'    => 'Contracts/ContactFormHookInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\EmailSenderInterface'        => 'Contracts/EmailSenderInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\EmailTemplateInterface'      => 'Contracts/EmailTemplateInterface.php',
    'rafalmasiarek\\ContactForm\\Contracts\\AttemptLoggerInterface'      => 'Contracts/AttemptLoggerInterface.php',

    'rafalmasiarek\\ContactForm\\Model\\ContactData'         => 'Model/ContactData.php',
    'rafalmasiarek\\ContactForm\\Model\\OutboundEmail'       => 'Model/OutboundEmail.php',

    'rafalmasiarek\\ContactForm\\Support\\ArrayMessageResolver'  => 'Support/ArrayMessageResolver.php',
    'rafalmasiarek\\ContactForm\\Support\\ContactDataValidator'  => 'Support/ContactDataValidator.php',
    'rafalmasiarek\\ContactForm\\Support\\NullLogger'            => 'Support/NullLogger.php',

    'rafalmasiarek\\ContactForm\\Mail\\PhpMailerEmailSender'     => 'Mail/PhpMailerEmailSender.php',

    'rafalmasiarek\\ContactForm\\Http\\IpResolverInterface'      => 'Http/IpResolverInterface.php',
    'rafalmasiarek\\ContactForm\\Http\\DefaultIpResolver'        => 'Http/DefaultIpResolver.php',
    'rafalmasiarek\\ContactForm\\Http\\Psr7IpResolver'           => 'Http/Psr7IpResolver.php',
];

spl_autoload_register(static function (string $class) use ($__cf_prefix, $__cf_baseDir, $__cf_classmap): void {
    if (strncmp($class, $__cf_prefix, strlen($__cf_prefix)) !== 0) {
        return;
    }
    if (isset($__cf_classmap[$class])) {
        $file = $__cf_baseDir . $__cf_classmap[$class];
        if (is_file($file)) { require $file; }
        return;
    }
    $relative = substr($class, strlen($__cf_prefix));
    $file = $__cf_baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';
    if (is_file($file)) { require $file; return; }
});
