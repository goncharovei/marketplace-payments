<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Repository\PaymentRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrinePaymentRepository;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PaymentRepository::class,
            DoctrinePaymentRepository::class,
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
