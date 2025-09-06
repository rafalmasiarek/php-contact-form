<?php

declare(strict_types=1);

return [
    'smtp' => [
        'host'      => getenv('SMTP_HOST') ?: 'mailhog',
        'port'      => (int)(getenv('SMTP_PORT') ?: 1025),
        'username'  => getenv('SMTP_USER') ?: '',
        'password'  => getenv('SMTP_PASS') ?: '',
        'secure'    => getenv('SMTP_SECURE') ?: '', // '', 'tls', 'ssl'
        'from'      => getenv('SMTP_FROM') ?: 'no-reply@example.test',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'ContactForm Demo',
        'to'        => getenv('SMTP_TO') ?: 'inbox@example.test',
    ],

    'captcha' => [
        'field'       => 'captcha_answer',   // name="" in the form
        'meta_key'    => 'captcha_answer',   // where hook stores the value in meta
        'session_key' => 'captcha_expected', // where generateChallenge stores the expected result
        'one_shot'    => true,               // unset expected after first check
    ],
];
