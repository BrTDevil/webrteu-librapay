<?php

declare(strict_types=1);

use WeBRTeu\LibraPay\LibraPayStatusMap;
use WeBRTeu\Payments\Enums\PaymentStatus;

it('maps ACTION and RC', function (string $action, string $rc, PaymentStatus $expected): void {
    expect(LibraPayStatusMap::resolve($action, $rc))->toBe($expected);
})->with([
    ['0', '00', PaymentStatus::Paid],
    ['0', '05', PaymentStatus::Failed],
    ['1', '00', PaymentStatus::Unknown],
    ['2', '51', PaymentStatus::Failed],
    ['3', '', PaymentStatus::Failed],
    ['9', '', PaymentStatus::Unknown],
]);
