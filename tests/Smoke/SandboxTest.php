<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use WeBRTeu\Payments\Facades\Payments;

it('posts the payment form to the Libra test gateway (card 4111111111111111)', function (): void {
    $redirect = Payments::start('librapay', paymentRequest('SMOKE-'.time()));

    expect(Http::asForm()->post($redirect->url, $redirect->fields)->successful())->toBeTrue();
})->group('sandbox')->skip(! env('LIBRAPAY_SANDBOX_TERMINAL'), 'Setează LIBRAPAY_SANDBOX_URL / _TERMINAL / _KEY.');
