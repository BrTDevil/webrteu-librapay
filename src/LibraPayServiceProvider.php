<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use WeBRTeu\Payments\PaymentManager;

class LibraPayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/librapay.php', 'librapay');

        $this->app->singleton(LibraPayGateway::class);

        $this->callAfterResolving(PaymentManager::class, function (PaymentManager $payments): void {
            $payments->extend('librapay', fn (Container $app): LibraPayGateway => $app->make(LibraPayGateway::class));
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/librapay.php' => $this->app->configPath('librapay.php'),
        ], 'librapay-config');

        $this->publishes([
            __DIR__.'/../database/migrations/add_librapay_order_to_payments.php.stub' => $this->app->databasePath('migrations/'.date('Y_m_d_His').'_add_librapay_order_to_payments.php'),
        ], 'librapay-migrations');
    }
}
