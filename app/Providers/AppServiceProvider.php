<?php

namespace App\Providers;

use App\Billing\Gateways\PaymentGateway;
use App\Billing\Gateways\PaystackGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Payment gateway driver — config-only swap (D-037 / D-084).
        $this->app->bind(PaymentGateway::class, function () {
            return match ((string) config('ics.billing.gateway', 'paystack')) {
                'paystack' => new PaystackGateway,
                default => new PaystackGateway,
            };
        });
    }

    public function boot(): void
    {
        //
    }
}
