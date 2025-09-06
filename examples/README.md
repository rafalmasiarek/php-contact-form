# ContactForm Demo (php -S + MailHog)

Minimalny przykład uruchamiany lokalnie przez **PHP built‑in server** i **MailHog** (SMTP + UI).
Możesz go użyć bez Composera — biblioteka ładowana jest przez `lib/contactform/autoload.php`.

## 1) Skopiuj bibliotekę do `lib/contactform/src`

Skopiuj zawartość katalogu `src/` z Twojej biblioteki **ContactForm** do:
```
lib/contactform/src/
```

> Jeśli nie masz paczki pod ręką, wcześniej przygotowaliśmy ją jako `contactform-structured-interfaces.zip`.

## 2) Uruchom przez docker-compose (rekomendowane)

```bash
docker compose up --build
# app:   http://localhost:8080
# mailhog UI: http://localhost:8025
```

Zmienisz adresy nadawcy/odbiorcy przez zmienne środowiskowe w `docker-compose.yml`.

## 3) Lub bez Dockera (lokalny PHP)

Wymaga PHP 8.2+:
```bash
php -S 127.0.0.1:8080 -t public
# otwórz http://127.0.0.1:8080
```

> W tym trybie wysyłka maili zadziała, jeśli MailHog działa lokalnie na `localhost:1025` albo poprawisz `config.php`.

## Struktura

```
contactform-example/
├─ docker-compose.yml
├─ config.php
├─ lib/
│  └─ contactform/
│     ├─ autoload.php
│     └─ src/                # <<< tu wgraj bibliotekę (PSR-4: rafalmasiarek\ContactForm\)
└─ public/
   ├─ index.php              # formularz + endpoint POST /send
   └─ style.css
```

Powodzenia!
