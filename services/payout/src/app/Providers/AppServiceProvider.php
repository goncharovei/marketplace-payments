<?php

namespace App\Providers;

use App\Domain\Event\DomainEventPublisher;
use App\Domain\Repository\PayoutRepository;
use App\Domain\Repository\SellerBalanceRepository;
use App\Domain\Service\PayoutGateway;
use App\Infrastructure\Messaging\EcotoneDomainEventPublisher;
use App\Infrastructure\Persistence\Doctrine\DoctrinePayoutRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrineSellerBalanceRepository;
use App\Infrastructure\Service\FakePayoutGateway;
use Enqueue\AmqpExt\AmqpConnectionFactory;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            PayoutRepository::class,
            DoctrinePayoutRepository::class,
        );

        $this->app->singleton(
            SellerBalanceRepository::class,
            DoctrineSellerBalanceRepository::class,
        );

        $this->app->singleton(
            DomainEventPublisher::class,
            EcotoneDomainEventPublisher::class,
        );

        $this->app->singleton(FakePayoutGateway::class);
        $this->app->alias(FakePayoutGateway::class, PayoutGateway::class);

        $this->app->singleton(AmqpConnectionFactory::class, function (): AmqpConnectionFactory {
            return new AmqpConnectionFactory(config('amqp.dsn'));
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
