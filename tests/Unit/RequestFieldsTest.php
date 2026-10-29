<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WeBRTeu\LibraPay\RequestFields;
use WeBRTeu\Payments\Support\LengthPrefixedHmac;

function lpConfig(): array
{
    return [
        'terminal' => '60000123', 'merchant' => '000000060000123', 'merchant_name' => 'Magazin SRL', 'merchant_url' => 'https://shop.example',
        'email' => 'plati@shop.example', 'key' => LP_KEY, 'country' => 'RO', 'merch_gmt' => '+2', 'trtype' => '0', 'backref' => 'https://shop.example/back',
    ];
}

it('signs the fields in the configured order with P_SIGN (HMAC-SHA1)', function (): void {
    $order = config('librapay.fields.request');
    $fields = RequestFields::make(paymentRequest(), '100001', lpConfig(), $order, CarbonImmutable::parse('2026-10-28 12:00:00', 'Europe/Bucharest'), 'aabb');

    expect(array_slice(array_keys($fields), 0, count($order)))->toBe($order)
        ->and($fields['AMOUNT'])->toBe('141.70')
        ->and($fields['DESC'])->toBe('Comanda #123')
        ->and($fields['TIMESTAMP'])->toBe('20261028100000')
        // ⚠️ vector fix calculat cu LengthPrefixedHmac; de înlocuit cu exemplul oficial Libra.
        ->and($fields['P_SIGN'])->toBe(LengthPrefixedHmac::sign(array_map(fn (string $f): string => $fields[$f], $order), LP_KEY, 'sha1'))
        ->and(strlen($fields['P_SIGN']))->toBe(40)
        ->and($fields)->toMatchArray(['FIRST_NAME' => 'Ion', 'CUSTOMER_EMAIL' => 'ion@example.com']);
});

it('follows a different configured order and leaves unknown fields empty', function (): void {
    $fields = RequestFields::make(paymentRequest(), '100001', lpConfig(), ['ORDER', 'AMOUNT', 'EXTRA']);

    expect(array_slice(array_keys($fields), 0, 3))->toBe(['ORDER', 'AMOUNT', 'EXTRA'])
        ->and($fields['EXTRA'])->toBe('')
        ->and($fields['NONCE'])->toHaveLength(32)
        ->and($fields['P_SIGN'])->toBe(LengthPrefixedHmac::sign(['100001', '141.70', ''], LP_KEY, 'sha1'));
});
