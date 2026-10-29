<?php

declare(strict_types=1);

/*
| ⚠️ Câmpurile, ordinea lor pentru P_SIGN și valorile TRTYPE / ACTION sunt cele tipice protocolului „e-Commerce Gateway”
| și se confirmă cu documentația oficială Libra. Stau aici, nu în cod, ca să poată fi corectate fără o versiune nouă.
*/
return [

    'sandbox' => (bool) env('LIBRAPAY_SANDBOX', true),

    /*
    | URL-ul formularului din documentația Libra (test / producție).
    */
    'url' => env('LIBRAPAY_URL'),

    'terminal' => env('LIBRAPAY_TERMINAL'),
    'merchant' => env('LIBRAPAY_MERCHANT'),
    'merchant_name' => env('LIBRAPAY_MERCHANT_NAME'),
    'merchant_url' => env('LIBRAPAY_MERCHANT_URL'),
    'email' => env('LIBRAPAY_EMAIL'),

    /*
    | Cheia de criptare: 32 de caractere hex.
    */
    'key' => env('LIBRAPAY_KEY'),

    'currency' => 'RON',
    'country' => 'RO',
    'merch_gmt' => env('LIBRAPAY_MERCH_GMT', '+2'),

    'fields' => [
        'request' => ['AMOUNT', 'CURRENCY', 'ORDER', 'DESC', 'MERCH_NAME', 'MERCH_URL', 'MERCHANT', 'TERMINAL', 'EMAIL', 'TRTYPE', 'COUNTRY', 'MERCH_GMT', 'TIMESTAMP', 'NONCE', 'BACKREF'],
        'response' => ['TERMINAL', 'TRTYPE', 'ORDER', 'AMOUNT', 'CURRENCY', 'DESC', 'ACTION', 'RC', 'MESSAGE', 'RRN', 'INT_REF', 'APPROVAL', 'TIMESTAMP', 'NONCE'],
    ],

    'trtype' => [
        'sale' => '0',
    ],

    /*
    | ACTION → status: 0 aprobată, 1 duplicat (ignorat), 2 / 3 refuzată / eroare.
    */
    'actions' => [
        'approved' => ['0'],
        'duplicate' => ['1'],
        'failed' => ['2', '3'],
    ],

    /*
    | ORDER numeric: lungime minimă (cu zerouri în față).
    */
    'order_min_length' => 6,

    /*
    | Vechimea maximă acceptată a TIMESTAMP-ului din răspuns, în minute (protecție la replay).
    */
    'timestamp_tolerance_minutes' => 60,

    /*
    | Corpul răspunsului la IPN, dacă Libra cere unul anume.
    */
    'ipn_response' => env('LIBRAPAY_IPN_RESPONSE', '1'),

    /*
    | null = route('payments.webhook', 'librapay').
    */
    'backref' => env('LIBRAPAY_BACKREF'),

];
