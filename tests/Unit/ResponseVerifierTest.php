<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use WeBRTeu\LibraPay\ResponseVerifier;
use WeBRTeu\Payments\Exceptions\InvalidSignatureException;

function verifyIpn(array $fields, ?int $amount = null): void
{
    ResponseVerifier::verify($fields, config('librapay.fields.response'), LP_KEY, '60000123', 60, $amount);
}

it('accepts a valid response', function (): void {
    verifyIpn(ipnFields(), 14170);

    expect(true)->toBeTrue();
});

it('rejects tampered, foreign or old responses', function (Closure $fields, ?int $amount, string $message): void {
    expect(fn () => verifyIpn($fields(), $amount))->toThrow(InvalidSignatureException::class, $message);
})->with([
    'AMOUNT modificat' => [fn (): array => ['AMOUNT' => '1.00'] + ipnFields(), null, 'P_SIGN'],
    'fără P_SIGN' => [fn (): array => ipnFields(sign: false), null, 'P_SIGN'],
    'TERMINAL străin' => [fn (): array => ipnFields(['TERMINAL' => '99999999']), null, 'TERMINAL'],
    'sumă diferită' => [fn (): array => ipnFields(), 100, 'Suma'],
    'TIMESTAMP vechi' => [fn (): array => ipnFields(['TIMESTAMP' => CarbonImmutable::now()->subHours(2)->utc()->format('YmdHis')]), null, 'TIMESTAMP'],
    'TIMESTAMP invalid' => [fn (): array => ipnFields(['TIMESTAMP' => 'ieri']), null, 'TIMESTAMP'],
]);
