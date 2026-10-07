<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Event\DomainEventPublisher;
use App\Domain\Repository\PaymentRepository;
use App\Infrastructure\Messaging\EcotoneDomainEventPublisher;
use App\Infrastructure\Persistence\Doctrine\DoctrinePaymentRepository;
use Enqueue\AmqpExt\AmqpConnectionFactory;
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

        $this->app->singleton(
            DomainEventPublisher::class,
            EcotoneDomainEventPublisher::class,
        );

        $this->app->singleton(
            AmqpConnectionFactory::class,
            function (): AmqpConnectionFactory {
                return new AmqpConnectionFactory(config('amqp.dsn'));
            },
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
