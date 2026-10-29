<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Support\LengthPrefixedHmac;
use WeBRTeu\Payments\Support\Money;

/**
 * Câmpurile formularului LibraPay + P_SIGN (HMAC-SHA1), în ordinea din `librapay.fields.request`.
 */
final class RequestFields
{
    /**
     * @param  array{terminal: string, merchant: string, merchant_name: string, merchant_url: string, email: string, key: string, country: string, merch_gmt: string, trtype: string, backref: string}  $config
     * @param  list<string>  $order  ordinea câmpurilor semnate
     * @return array<string, string>
     */
    public static function make(PaymentRequestData $request, string $orderNumber, array $config, array $order, ?CarbonImmutable $now = null, ?string $nonce = null): array
    {
        $values = [
            'AMOUNT' => Money::toDecimalString($request->amount),
            'CURRENCY' => $request->currency,
            'ORDER' => $orderNumber,
            'DESC' => Str::limit(Str::ascii($request->description), 50, ''),
            'MERCH_NAME' => $config['merchant_name'],
            'MERCH_URL' => $config['merchant_url'],
            'MERCHANT' => $config['merchant'],
            'TERMINAL' => $config['terminal'],
            'EMAIL' => $config['email'],
            'TRTYPE' => $config['trtype'],
            'COUNTRY' => $config['country'],
            'MERCH_GMT' => $config['merch_gmt'],
            'TIMESTAMP' => ($now ?? CarbonImmutable::now())->utc()->format('YmdHis'),
            'NONCE' => $nonce ?? bin2hex(random_bytes(16)),
            'BACKREF' => $config['backref'],
        ];

        $signed = [];

        foreach ($order as $field) {
            $signed[$field] = $values[$field] ?? '';
        }

        return $signed + $values + [
            'P_SIGN' => LengthPrefixedHmac::sign(array_values($signed), $config['key'], 'sha1'),
            'FIRST_NAME' => $request->customer->firstName,
            'LAST_NAME' => $request->customer->lastName,
            'CUSTOMER_EMAIL' => $request->customer->email,
            'PHONE' => (string) $request->customer->phone,
        ];
    }
}
