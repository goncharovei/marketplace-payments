<?php

namespace App\Providers;

use App\Domain\Repository\OrderRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineOrderRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            OrderRepository::class,
            DoctrineOrderRepository::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
