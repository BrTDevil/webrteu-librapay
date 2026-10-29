<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;
use WeBRTeu\Payments\Enums\PaymentStatus;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Models\Payment;

function postIpn(array $fields): TestResponse
{
    return test()->call('POST', '/webhooks/payments/librapay', [], [], [], ['CONTENT_TYPE' => 'application/x-www-form-urlencoded'], http_build_query($fields));
}

beforeEach(function (): void {
    Payments::start('librapay', paymentRequest());
    $this->order = (string) Payment::query()->sole()->getAttribute('librapay_order');
});

it('marks an approved IPN as Paid and answers with the configured body', function (): void {
    postIpn(ipnFields(['ORDER' => $this->order]))->assertOk()->assertContent('1');

    expect(Payment::query()->sole())->status->toBe(PaymentStatus::Paid)->provider_payment_id->toBe('A1B2C3D4E5F6');
});

it('does not change anything for a duplicate (ACTION=1)', function (): void {
    postIpn(ipnFields(['ORDER' => $this->order, 'ACTION' => '1']))->assertOk();

    expect(Payment::query()->sole()->status)->toBe(PaymentStatus::Pending);
});

it('marks a declined IPN as Failed', function (): void {
    postIpn(ipnFields(['ORDER' => $this->order, 'ACTION' => '2', 'RC' => '51']))->assertOk();

    expect(Payment::query()->sole()->status)->toBe(PaymentStatus::Failed);
});

it('rejects an invalid P_SIGN', function (): void {
    postIpn(['AMOUNT' => '1.00'] + ipnFields(['ORDER' => $this->order]))->assertStatus(400)->assertContent('0');
});

it('uses a custom IPN response body', function (): void {
    Config::set('librapay.ipn_response', 'OK');

    postIpn(ipnFields(['ORDER' => $this->order]))->assertContent('OK');
});
