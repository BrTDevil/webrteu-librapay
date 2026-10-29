# webrteu/librapay

[![PHP](https://img.shields.io/badge/PHP-%5E8.2-777bb4)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12%E2%80%9313-ff2d20)](https://laravel.com/)
[![Licență MIT](https://img.shields.io/badge/licen%C8%9B%C4%83-MIT-green)](LICENSE.md)
[![Packagist](https://img.shields.io/packagist/v/webrteu/librapay)](https://packagist.org/packages/webrteu/librapay)

Integrare Laravel pentru **LibraPay** (Libra Internet Bank): plată pe pagina găzduită printr-un formular POST semnat
**P_SIGN (HMAC-SHA1)**, confirmare prin IPN. Implementează `Gateway` din `webrteu/payments`.

Nu este un pachet oficial Libra Internet Bank.

> ⚠️ **Neverificat pe API real.** Protocolul urmează tiparul „e-Commerce Gateway” (câmpuri în majuscule + P_SIGN).
> Ordinea câmpurilor, valorile `TRTYPE` / `ACTION`, URL-urile și răspunsul la IPN stau în `config/librapay.php` și
> trebuie confirmate unul câte unul cu documentația oficială primită de la Libra la semnarea contractului.

## Cerințe

- PHP `^8.2`, Laravel 12–13
- contract LibraPay (Terminal ID, Merchant ID, cheie de 32 hex)
- `webrteu/payments` (cu migrarea `payments` deja rulată)

## Instalare

```bash
composer require webrteu/librapay
php artisan vendor:publish --tag=librapay-config
php artisan vendor:publish --tag=librapay-migrations   # coloana payments.librapay_order
php artisan migrate
```

## Configurare

```dotenv
LIBRAPAY_SANDBOX=true
LIBRAPAY_URL=                  # URL-ul formularului din documentația Libra
LIBRAPAY_TERMINAL=             # 8 cifre
LIBRAPAY_MERCHANT=             # 15 cifre (de regulă 0000000 + Terminal ID)
LIBRAPAY_MERCHANT_NAME=
LIBRAPAY_MERCHANT_URL=
LIBRAPAY_EMAIL=
LIBRAPAY_KEY=                  # 32 caractere hex
LIBRAPAY_MERCH_GMT=+2
LIBRAPAY_IPN_RESPONSE=1        # corpul cerut la confirmarea IPN ⚠️
LIBRAPAY_BACKREF=              # gol = https://site.ro/webhooks/payments/librapay
```

## Utilizare

```php
$redirect = Payments::start('librapay', $paymentRequest, payable: $order);
return $redirect->toResponse();   // formular POST trimis automat
```

`ORDER` trebuie să fie numeric, cu lungime minimă: pachetul îl derivă din id-ul rândului `payments`
(`100000 + id`, completat cu zerouri până la `order_min_length`) și păstrează maparea în `payments.librapay_order`.
IPN-ul e tradus înapoi automat în `order_id`-ul aplicației.

## Webhook (IPN)

- URL: `https://site.ro/webhooks/payments/librapay` (comunicat la Libra sau trimis ca `BACKREF`, după docs)
- verificare: P_SIGN pe câmpurile din `fields.response`, `TERMINAL`, `AMOUNT` (prin core) și vechimea `TIMESTAMP`
  (maxim `timestamp_tolerance_minutes`, protecție la replay)
- idempotență: `INT_REF` + `TRTYPE` + `ACTION`

| `ACTION` / `RC` | `PaymentStatus` |
|---|---|
| `0` + `RC=00` | `Paid` |
| `0` + alt RC, `2`, `3` | `Failed` |
| `1` (duplicat) | ignorat (`Unknown` nu suprascrie nimic) |

## Ce nu face pachetul (încă)

- captură, anulare, refund (`TRTYPE` diferit, server-to-server): se adaugă după confirmarea valorilor în docs
- `status()` / reconciliere: `UnsupportedOperationException`

## Testare

```bash
composer test
LIBRAPAY_SANDBOX_URL=... LIBRAPAY_SANDBOX_TERMINAL=... LIBRAPAY_SANDBOX_KEY=... vendor/bin/pest --group=sandbox
```

Card de test: `4111111111111111`. Vectorii P_SIGN sunt calculați cu implementarea din core. **De înlocuit cu exemplul oficial Libra**.

## Changelog

Vezi [CHANGELOG.md](CHANGELOG.md).

## Autor

[WeBRTeu](https://webrt.eu)

## Licență

MIT. Vezi [LICENSE.md](LICENSE.md).
