<?php

namespace App\Providers;

use App\Repositories\ContractRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\PaymentScheduleRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ContractRepository::class, function ($app) {
            return new ContractRepository();
        });

        $this->app->singleton(PaymentRepository::class, function ($app) {
            return new PaymentRepository();
        });

        $this->app->singleton(PaymentScheduleRepository::class, function ($app) {
            return new PaymentScheduleRepository();
        });
    }

    /**
     * Bootstrap services.
     */
    public function bootstrap(): void
    {
        //
    }
}
