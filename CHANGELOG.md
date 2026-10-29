# Changelog

## [Unreleased]

### Adăugat
- `LibraPayGateway`: formular POST semnat P_SIGN (HMAC-SHA1 prin `LengthPrefixedHmac`), IPN verificat (P_SIGN, TERMINAL, AMOUNT, TIMESTAMP).
- `ORDER` numeric derivat din id-ul plății, mapat în coloana `payments.librapay_order`.
- Ordinea câmpurilor, valorile `TRTYPE` / `ACTION` și corpul răspunsului la IPN în config.
