# ContactForm Demo (php -S + MailHog)

Minimal example runnable locally with the **PHP built-in server** and **MailHog**.  
No copying required — the demo uses the library directly from the parent repository via `bootstrap.php` and the local autoloader in `lib/contactform/`.

---

## Quick start (Docker, recommended)

```bash
docker compose up --build
# app:        http://localhost:8080
# MailHog UI: http://localhost:8025
```

The demo container mounts the parent repo (`..`) into `/app` and serves `examples/public/`.  
Sender/recipient are configured via env vars in `docker-compose.yml`:

- `SMTP_HOST` (default: `mailhog`)
- `SMTP_PORT` (default: `1025`)
- `SMTP_FROM` (default: `no-reply@example.test`)
- `SMTP_FROM_NAME` (default: `ContactForm Demo`)
- `SMTP_TO` (default: `inbox@example.test`)

---

## Quick start (without Docker)

Requires PHP 8.2+ and a local MailHog on `localhost:1025`.

```bash
php -S 127.0.0.1:8080 -t public
# open http://127.0.0.1:8080
```

If MailHog isn’t running, either start it with Docker:

```bash
docker run --rm -p 1025:1025 -p 8025:8025 mailhog/mailhog:v1.0.1
```

…or adjust SMTP settings in `config.php`.

---

## Repository layout (demo)

```
.
├── bootstrap.php
├── config.php
├── docker-compose.yml
├── Dockerfile.php
├── lib
│   └── contactform
│       ├── autoload.php
│       ├── Hooks
│       │   ├── AnnotateIpHook.php
│       │   └── MathCaptchaHook.php
│       └── Validators
│           ├── FieldsValidator.php
│           └── MathCaptchaValidator.php
├── public
│   ├── index.php
│   └── style.css
└── README.md
```

- `bootstrap.php` – resolves autoloading (Composer or project autoloader).
- `config.php` – SMTP + CAPTCHA configuration (field name, session key, one-shot).
- `lib/contactform/autoload.php` – standalone autoloader for demo add-ons.
- Hooks:
  - `AnnotateIpHook` – adds client IP/UA to `ContactData->meta`.
  - `MathCaptchaHook` – lifts the CAPTCHA answer from the request into `meta`.
- Validators:
  - `FieldsValidator` – required fields + email format.
  - `MathCaptchaValidator` – simple arithmetic CAPTCHA.
- `public/index.php` – GET: renders form & generates CAPTCHA. POST: `/send` endpoint.
- `public/style.css` – minimal styles.

---

## How it works

- **GET /** – generates a new math CAPTCHA question and stores the expected answer in `$_SESSION`.
- **POST /send** – runs hooks and validators, then sends an email using:
  - PHPMailer SMTP if `PHPMailer\PHPMailer\PHPMailer` is available, or
  - native `mail()` if available (requires a working `sendmail_path`), otherwise returns an error.

The endpoint responds with JSON:

```json
{ "ok": true|false, "code": "OK_SENT|ERR_*", "message": "...", "meta": { ... } }
```

---

## Troubleshooting

- **`ERR_NO_SENDER`**: PHPMailer not installed and native `mail()` not available. Enable one of them.
- **CAPTCHA always invalid**: don’t regenerate the challenge on POST; ensure `session_start()` is called.
- **Ports in use**: change `8080`/`8025` in `docker-compose.yml`.
- **Apple Silicon (arm64)**: `mailhog/mailhog:v1.0.1` works on arm64; if Docker warns about platform, pin the platform in Compose.

---

## License

MIT (or your preferred license).

Good Luck 🤞
