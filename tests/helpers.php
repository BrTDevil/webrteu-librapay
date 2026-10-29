<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use WeBRTeu\Payments\Data\CustomerData;
use WeBRTeu\Payments\Data\PaymentRequestData;
use WeBRTeu\Payments\Support\LengthPrefixedHmac;

const LP_KEY = '00112233445566778899AABBCCDDEEFF';

function paymentRequest(string $orderId = '123-1', int $amount = 14170): PaymentRequestData
{
    return new PaymentRequestData(
        orderId: $orderId,
        amount: $amount,
        description: 'Comandă #123',
        customer: new CustomerData('ion@example.com', 'Ion', 'Popescu', '0721000000'),
        returnUrl: 'https://shop.example/plata/librapay/return',
    );
}

/**
 * Câmpurile unui IPN LibraPay semnate corect cu ordinea din config.
 *
 * @param  array<string, string>  $overrides
 * @return array<string, string>
 */
function ipnFields(array $overrides = [], bool $sign = true): array
{
    $fields = $overrides + [
        'TERMINAL' => '60000123',
        'TRTYPE' => '0',
        'ORDER' => '100001',
        'AMOUNT' => '141.70',
        'CURRENCY' => 'RON',
        'DESC' => 'Comanda #123',
        'ACTION' => '0',
        'RC' => '00',
        'MESSAGE' => 'Approved',
        'RRN' => '612345678901',
        'INT_REF' => 'A1B2C3D4E5F6',
        'APPROVAL' => '123456',
        'TIMESTAMP' => CarbonImmutable::now()->utc()->format('YmdHis'),
        'NONCE' => 'aabbccddeeff00112233445566778899',
    ];

    if ($sign) {
        /** @var list<string> $order */
        $order = Config::get('librapay.fields.response');
        $fields['P_SIGN'] = LengthPrefixedHmac::sign(array_map(fn (string $field): string => $fields[$field] ?? '', $order), LP_KEY, 'sha1');
    }

    return $fields;
}
