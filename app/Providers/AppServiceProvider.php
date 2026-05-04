<?php

namespace App\Providers;

use App\Services\SadadPaymentService;
use App\Services\SadadSignatureService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind SADAD signature service with the resolved secret key from config
        $this->app->singleton(SadadSignatureService::class, function () {
            return new SadadSignatureService(
                secretKey: config('sadad.secret_key', '')
            );
        });

        // Bind SADAD payment service with its dependency
        $this->app->singleton(SadadPaymentService::class, function ($app) {
            return new SadadPaymentService(
                signatureService: $app->make(SadadSignatureService::class)
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
