<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use WeBRTeu\LibraPay\OrderNumber;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Models\Payment;

function lpPayment(string $orderId): Payment
{
    return Payment::query()->create(['gateway' => 'librapay', 'order_id' => $orderId, 'amount' => 100, 'status' => PaymentStatus::Pending]);
}

it('generates a numeric, unique ORDER and maps it back', function (): void {
    $first = lpPayment('A-1');
    $second = lpPayment('B-2');

    $a = OrderNumber::for('A-1');
    $b = OrderNumber::for('B-2');

    expect($a)->toBe((string) (OrderNumber::START + $first->id))->toMatch('/^\d{6,}$/')
        ->and($b)->toBe((string) (OrderNumber::START + $second->id))->not->toBe($a)
        ->and(OrderNumber::for('A-1'))->toBe($a)
        ->and(OrderNumber::resolve($a))->toBe('A-1')
        ->and(OrderNumber::resolve($b))->toBe('B-2')
        ->and($first->refresh()->getAttribute('librapay_order'))->toBe($a);
});

it('pads to the minimum length', function (): void {
    Config::set('librapay.order_min_length', 10);
    lpPayment('A-1');

    expect(OrderNumber::for('A-1'))->toHaveLength(10)->toStartWith('0000');
});

it('falls back to a random number and to ORDER when nothing is mapped', function (): void {
    expect(OrderNumber::for('fara-rand'))->toMatch('/^\d{6,}$/')
        ->and(OrderNumber::resolve('555555'))->toBe('555555');
});
