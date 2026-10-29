<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use WeBRTeu\Payments\Exceptions\InvalidSignatureException;
use WeBRTeu\Payments\Support\LengthPrefixedHmac;
use WeBRTeu\Payments\Support\Money;

/**
 * Verifică răspunsul LibraPay (BACKREF și IPN): P_SIGN, TERMINAL, AMOUNT, vechimea TIMESTAMP.
 */
final class ResponseVerifier
{
    /**
     * @param  array<string, string>  $fields
     * @param  list<string>  $order
     */
    public static function verify(array $fields, array $order, string $key, string $terminal, int $toleranceMinutes, ?int $expectedAmount = null, ?CarbonImmutable $now = null): void
    {
        $values = array_map(fn (string $field): string => $fields[$field] ?? '', $order);

        if (($fields['P_SIGN'] ?? '') === '' || ! LengthPrefixedHmac::verify($values, $key, 'sha1', $fields['P_SIGN'])) {
            throw new InvalidSignatureException('P_SIGN LibraPay nu este valid.');
        }

        if (($fields['TERMINAL'] ?? null) !== $terminal) {
            throw new InvalidSignatureException('TERMINAL nu aparține acestui cont LibraPay.');
        }

        if ($expectedAmount !== null && Money::fromDecimal($fields['AMOUNT'] ?? '0') !== $expectedAmount) {
            throw new InvalidSignatureException('Suma din răspunsul LibraPay nu corespunde comenzii.');
        }

        $timestamp = DateTimeImmutable::createFromFormat('!YmdHis', $fields['TIMESTAMP'] ?? '', new DateTimeZone('UTC'));
        $now ??= CarbonImmutable::now();

        if ($timestamp === false || abs($now->getTimestamp() - $timestamp->getTimestamp()) > $toleranceMinutes * 60) {
            throw new InvalidSignatureException('TIMESTAMP-ul răspunsului LibraPay e prea vechi sau invalid.');
        }
    }
}
