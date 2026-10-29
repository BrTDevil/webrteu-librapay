<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Illuminate\Support\Facades\Config;
use WeBRTeu\Payments\Enums\PaymentStatus;

/**
 * ACTION / RC → PaymentStatus. Duplicatul (ACTION=1) devine Unknown, care nu suprascrie niciodată nimic.
 */
final class LibraPayStatusMap
{
    public static function resolve(string $action, string $rc): PaymentStatus
    {
        if (in_array($action, self::actions('approved'), true)) {
            return $rc === '00' ? PaymentStatus::Paid : PaymentStatus::Failed;
        }

        if (in_array($action, self::actions('failed'), true)) {
            return PaymentStatus::Failed;
        }

        return PaymentStatus::Unknown;
    }

    /**
     * @return list<string>
     */
    private static function actions(string $type): array
    {
        return array_values(array_map(strval(...), Config::array("librapay.actions.{$type}", [])));
    }
}
