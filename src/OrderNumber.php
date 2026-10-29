<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Illuminate\Support\Facades\Config;
use WeBRTeu\Payments\Models\Payment;

/**
 * LibraPay cere `ORDER` numeric, cu lungime minimă. Pachetul îl derivă din id-ul rândului `payments`
 * (unic prin construcție) și păstrează maparea ORDER ↔ order_id în coloana `payments.librapay_order`.
 */
final class OrderNumber
{
    public const START = 100000;

    public static function for(string $orderId): string
    {
        $payment = Payment::query()->where('gateway', 'librapay')->where('order_id', $orderId)->first();

        if (! $payment instanceof Payment) {
            // Plată pornită fără Payments::start: nu există rând de mapat, deci un număr aleator.
            return self::pad((string) random_int(self::START, 999_999_999));
        }

        $existing = $payment->getAttribute('librapay_order');

        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $order = self::pad((string) (self::START + $payment->id));
        $payment->forceFill(['librapay_order' => $order])->save();

        return $order;
    }

    /**
     * ORDER numeric → order_id-ul aplicației (sau ORDER, dacă plata nu a pornit prin Payments::start).
     */
    public static function resolve(string $order): string
    {
        $orderId = Payment::query()->where('gateway', 'librapay')->where('librapay_order', $order)->value('order_id');

        return is_string($orderId) ? $orderId : $order;
    }

    private static function pad(string $order): string
    {
        return str_pad($order, Config::integer('librapay.order_min_length', 6), '0', STR_PAD_LEFT);
    }
}
