<?php

namespace LyraPayment;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LyraPayment\Contracts\TransactionIdGenerator;
use LyraPayment\TransactionIds\CacheTransactionIdGenerator;
use LyraPayment\TransactionIds\TimeTransactionIdGenerator;

class LyraPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lyra-payment.php', 'lyra-payment');

        $this->app->bind(TransactionIdGenerator::class, function (Application $app) {
            $generator = $app['config']->get('lyra-payment.trans_id_generator', 'time');

            return match ($generator) {
                'time' => new TimeTransactionIdGenerator(),
                'cache' => new CacheTransactionIdGenerator(
                    $app['cache']->store($app['config']->get('lyra-payment.trans_id_cache_store'))
                ),
                default => $app->make($generator),
            };
        });

        $this->app->singleton(LyraPayment::class, fn (Application $app) => new LyraPayment(
            $app['config']->get('lyra-payment'),
            $app->make(TransactionIdGenerator::class),
            $app['router'],
            $app['url'],
        ));
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'lyra-payment');
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'lyra-payment');

        if ($this->app['config']->get('lyra-payment.routes.enabled', true) && ! $this->app->routesAreCached()) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/lyra-payment.php');
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/lyra-payment.php' => config_path('lyra-payment.php'),
            ], 'lyra-payment-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/lyra-payment'),
            ], 'lyra-payment-views');

            $this->publishes([
                __DIR__ . '/../resources/lang' => $this->app->langPath('vendor/lyra-payment'),
            ], 'lyra-payment-translations');
        }
    }
}
