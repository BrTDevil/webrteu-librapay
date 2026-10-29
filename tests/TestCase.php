<?php

declare(strict_types=1);

namespace WeBRTeu\LibraPay\Tests;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\URL;
use Orchestra\Testbench\TestCase as Orchestra;
use WeBRTeu\LibraPay\LibraPayServiceProvider;
use WeBRTeu\Payments\PaymentsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // URL-urile generate (notifyUrl, BACKREF...) folosesc app.url, nu host-ul cererii de test.
        URL::forceRootUrl('https://shop.example');
        URL::forceScheme('https');
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [PaymentsServiceProvider::class, LibraPayServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('app.url', 'https://shop.example');
        $app['config']->set('librapay.url', env('LIBRAPAY_SANDBOX_URL', 'https://secure2.librapay.ro/test/form'));
        $app['config']->set('librapay.terminal', env('LIBRAPAY_SANDBOX_TERMINAL', '60000123'));
        $app['config']->set('librapay.merchant', '000000060000123');
        $app['config']->set('librapay.merchant_name', 'Magazin SRL');
        $app['config']->set('librapay.merchant_url', 'https://shop.example');
        $app['config']->set('librapay.email', 'plati@shop.example');
        $app['config']->set('librapay.key', env('LIBRAPAY_SANDBOX_KEY', '00112233445566778899AABBCCDDEEFF'));
    }

    protected function defineDatabaseMigrations(): void
    {
        foreach ([
            __DIR__.'/../vendor/webrteu/payments/database/migrations/create_payments_tables.php.stub',
            __DIR__.'/../database/migrations/add_librapay_order_to_payments.php.stub',
        ] as $stub) {
            /** @var Migration $migration */
            $migration = require $stub;
            $migration->up();
        }
    }
}
