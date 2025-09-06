<?php

declare(strict_types=1);

namespace rafalmasiarek\ContactForm\Mail;


use rafalmasiarek\ContactForm\Contracts\EmailSenderInterface;
use rafalmasiarek\ContactForm\Model\OutboundEmail;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use rafalmasiarek\ContactForm\Contracts\ErrorCodeProviderInterface;
use rafalmasiarek\ContactForm\Support\ArrayMessageResolver;

/**
 * Email sender implementation using PHPMailer with SMTP transport.
 *
 * All PHPMailer/SMTP-specific concerns live here:
 *  - local ERR_SMTP_* codes (as public constants),
 *  - mapping PHPMailer errors to those codes,
 *  - default human messages for those codes (registerDefaultMessages()).
 *
 * The service layer only relies on ErrorCodeProviderInterface; it never references SMTP symbols directly.
 */
final class PhpMailerEmailSender implements EmailSenderInterface
{
    // ---------- Transport-local stable codes (do NOT put them in the global Codes class) ----------
    public const ERR_SMTP_CONNECT = 'ERR_SMTP_CONNECT';
    public const ERR_SMTP_AUTH    = 'ERR_SMTP_AUTH';
    public const ERR_SMTP_FROM    = 'ERR_SMTP_FROM';
    public const ERR_SMTP_RCPT    = 'ERR_SMTP_RCPT';
    public const ERR_SMTP_DATA    = 'ERR_SMTP_DATA';
    public const ERR_SMTP_TLS     = 'ERR_SMTP_TLS';
    public const ERR_SMTP_UNKNOWN = 'ERR_SMTP_UNKNOWN';

    /**
     * SMTP configuration.
     *
     * Keys:
     * - host (string) – SMTP host (required)
     * - username (string) – SMTP username (optional)
     * - password (string) – SMTP password (optional)
     * - port (int) – SMTP port (default: 587)
     * - secure (string) – 'tls' or 'ssl' (optional)
     * - from (string) – From address (required)
     * - from_name (string) – From name (optional)
     * - to (string) – Recipient address (required)
     *
     * @var array{
     *     host:string,
     *     username?:string,
     *     password?:string,
     *     port?:int,
     *     secure?:string,
     *     from:string,
     *     from_name?:string,
     *     to:string
     * }
     */
    private array $smtp;

    /**
     * @param array{
     *     host:string,
     *     username?:string,
     *     password?:string,
     *     port?:int,
     *     secure?:string,
     *     from:string,
     *     from_name?:string,
     *     to:string
     * } $smtp
     */
    public function __construct(array $smtp)
    {
        $this->smtp = $smtp;
    }

    /**
     * Send the outbound email using PHPMailer and SMTP.
     *
     * @param  OutboundEmail $email Email message details.
     * @return bool                  True on success, false otherwise.
     *
     * @throws \RuntimeException              If PHPMailer is not installed.
     * @throws PhpMailerTransportException    On SMTP transport failure with a stable ERR_SMTP_* code.
     * @throws \Throwable                     On non-SMTP configuration/runtime failures.
     */
    public function send(OutboundEmail $email): bool
    {
        if (!class_exists(PHPMailer::class)) {
            throw new \RuntimeException(
                'PHPMailer is not installed. Require phpmailer/phpmailer or inject your own EmailSenderInterface.'
            );
        }

        $mail = new PHPMailer(true);
        try {
            // Transport & auth
            $mail->isSMTP();
            $mail->Host     = $this->smtp['host'];
            $mail->Port     = $this->smtp['port'] ?? 587;
            $mail->SMTPAuth = isset($this->smtp['username']);

            if ($mail->SMTPAuth) {
                $mail->Username = $this->smtp['username'];
                $mail->Password = $this->smtp['password'] ?? '';
            }
            if (!empty($this->smtp['secure'])) {
                $mail->SMTPSecure = $this->smtp['secure']; // 'tls' | 'ssl'
            }

            // Addresses & headers
            $mail->setFrom($this->smtp['from'], $this->smtp['from_name'] ?? 'Website');
            $mail->addAddress($this->smtp['to']);
            if (!empty($email->replyTo)) {
                $mail->addReplyTo($email->replyTo);
            }

            // Content
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject  = $email->subject;
            $mail->isHTML(true);
            $mail->Body     = $email->htmlBody;
            $mail->AltBody  = $email->textBody ?? strip_tags($email->htmlBody);

            // Attachments
            foreach ($email->attachments as $a) {
                if (\is_array($a) && isset($a['path'])) {
                    $mail->addAttachment($a['path'], $a['name'] ?? '');
                }
            }

            return (bool) $mail->send();
        } catch (PhpMailerException $e) {
            // Map PHPMailer errors to stable transport codes and rethrow a local ErrorCodeProviderInterface
            $mapped = self::mapSmtpError($mail->ErrorInfo ?? '', $e->getMessage());
            throw new PhpMailerTransportException($mapped['code'], $mapped['ctx'], $e);
        } catch (\Throwable $e) {
            // Non-PHPMailer errors (e.g., invalid configuration before send())
            throw $e;
        }
    }

    /**
     * Register default human-readable messages for this transport's ERR_SMTP_* codes.
     * Call this once when wiring your resolver (you may still override any texts per project/locale).
     */
    public static function registerDefaultMessages(ArrayMessageResolver $resolver): void
    {
        $resolver->extend([
            self::ERR_SMTP_CONNECT => ['message' => 'Could not connect to the SMTP server.',  'http' => 502],
            self::ERR_SMTP_AUTH    => ['message' => 'SMTP authentication failed.',            'http' => 502],
            self::ERR_SMTP_FROM    => ['message' => 'Invalid sender address.',                'http' => 502],
            self::ERR_SMTP_RCPT    => ['message' => 'Recipient address was rejected.',        'http' => 502],
            self::ERR_SMTP_DATA    => ['message' => 'SMTP server rejected the message data.', 'http' => 502],
            self::ERR_SMTP_TLS     => ['message' => 'Could not start TLS connection.',        'http' => 502],
            self::ERR_SMTP_UNKNOWN => ['message' => 'Unknown SMTP error.',                    'http' => 502],
        ]);
    }

    /**
     * Map PHPMailer's ErrorInfo/Exception message into a stable ERR_SMTP_* code.
     *
     * @param  string $errorInfo        PHPMailer::$ErrorInfo (may be empty)
     * @param  string $exceptionMessage Exception message from PHPMailer\Exception
     * @return array{code:string, ctx:array}
     */
    private static function mapSmtpError(string $errorInfo, string $exceptionMessage = ''): array
    {
        $haystack = \strtolower($errorInfo . ' ' . $exceptionMessage);

        $needles = [
            'smtp connect() failed'      => self::ERR_SMTP_CONNECT,
            'could not authenticate'     => self::ERR_SMTP_AUTH,
            'invalid address'            => self::ERR_SMTP_FROM,
            'recipient address rejected' => self::ERR_SMTP_RCPT,
            'rcpt to'                    => self::ERR_SMTP_RCPT,
            'data not accepted'          => self::ERR_SMTP_DATA,
            'could not start tls'        => self::ERR_SMTP_TLS,
            'starttls'                   => self::ERR_SMTP_TLS,
            'auth '                      => self::ERR_SMTP_AUTH,   // broad fallback
            'mail from'                  => self::ERR_SMTP_FROM,
        ];

        foreach ($needles as $needle => $code) {
            if (\str_contains($haystack, $needle)) {
                return ['code' => $code, 'ctx' => []];
            }
        }

        return ['code' => self::ERR_SMTP_UNKNOWN, 'ctx' => []];
    }
}

/**
 * Local SMTP transport exception for PHPMailer sender.
 *
 * This exception stays private to the PHPMailer implementation file,
 * implements ErrorCodeProvider and exposes a stable ERR_SMTP_* code.
 */
final class PhpMailerTransportException extends \RuntimeException implements ErrorCodeProviderInterface
{
    /** @var array<int|string,mixed> */
    private array $ctx;

    /**
     * @param string                      $errorCode Stable code (ERR_SMTP_*)
     * @param array<int|string,mixed>     $ctx       Optional resolver context
     * @param \Throwable|null             $prev      Previous exception
     */
    public function __construct(string $errorCode, array $ctx = [], ?\Throwable $prev = null)
    {
        $this->ctx = $ctx;
        // Store the symbolic code as message; it's never shown to the end-user
        parent::__construct($errorCode, 0, $prev);
    }

    /** @inheritDoc */
    public function getErrorCode(): string
    {
        return $this->getMessage();
    }

    /** @inheritDoc */
    public function getMessageContext(): array
    {
        return $this->ctx;
    }
}
