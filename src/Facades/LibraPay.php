<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay\Facades;

use Illuminate\Support\Facades\Facade;
use WeBRTeu\LibraPay\LibraPayGateway;

/**
 * @method static \WeBRTeu\Payments\Data\PaymentRedirectData createPayment(\WeBRTeu\Payments\Data\PaymentRequestData $request)
 *
 * @see LibraPayGateway
 */
class LibraPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return LibraPayGateway::class;
    }
}
