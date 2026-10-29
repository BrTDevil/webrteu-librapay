<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\LibraPay\Exceptions\LibraPayException;
use WeBRTeu\LibraPay\Facades\LibraPay;
use WeBRTeu\LibraPay\LibraPayGateway;
use WeBRTeu\LibraPay\LibraPayServiceProvider;
use WeBRTeu\Payments\Enums\RedirectType;
use WeBRTeu\Payments\Exceptions\UnsupportedOperationException;
use WeBRTeu\Payments\Facades\Payments;
use WeBRTeu\Payments\Models\Payment;

it('returns a Form with every configured field, in the configured order', function (): void {
    $redirect = Payments::start('librapay', paymentRequest());
    $payment = Payment::query()->sole();

    expect($redirect->type)->toBe(RedirectType::Form)
        ->and($redirect->url)->toBe('https://secure2.librapay.ro/test/form')
        ->and(array_slice(array_keys($redirect->fields), 0, 15))->toBe(config('librapay.fields.request'))
        ->and($redirect->fields['ORDER'])->toBe($payment->getAttribute('librapay_order'))
        ->and($redirect->fields['BACKREF'])->toBe('https://shop.example/webhooks/payments/librapay')
        ->and($redirect->fields['TERMINAL'])->toBe('60000123');
});

it('uses a configured BACKREF', function (): void {
    Config::set('librapay.backref', 'https://shop.example/back');

    expect(Payments::gateway('librapay')->createPayment(paymentRequest())->fields['BACKREF'])->toBe('https://shop.example/back');
});

it('requires the form URL and has no status API', function (): void {
    Config::set('librapay.url');

    expect(fn () => Payments::gateway('librapay')->createPayment(paymentRequest()))->toThrow(LibraPayException::class, 'LIBRAPAY_URL')
        ->and(fn () => Payments::gateway('librapay')->status('X'))->toThrow(UnsupportedOperationException::class);
});

it('registers the gateway and publishes the migration', function (): void {
    expect(Payments::available())->toContain('librapay')
        ->and(LibraPay::getFacadeRoot())->toBeInstanceOf(LibraPayGateway::class)
        ->and(ServiceProvider::pathsToPublish(LibraPayServiceProvider::class, 'librapay-migrations'))->toHaveCount(1);
});
