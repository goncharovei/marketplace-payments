<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Domain\Event\OrderCreated;
use Ecotone\Modelling\Attribute\EventHandler;
use Psr\Log\LoggerInterface;

/**
 * Handles the OrderCreated domain event.
 *
 * For now, it just logs. Later it will trigger notifications,
 * update read-models, publish to the message broker, etc.
 */
final readonly class OrderCreatedHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[EventHandler]
    public function handle(OrderCreated $event): void
    {
        $this->logger->info('Order created', [
            'orderId' => $event->orderId->toString(),
            'buyerId' => $event->buyerId->toString(),
            'sellerId' => $event->sellerId->toString(),
            'totalAmount' => $event->totalAmount->amount(),
            'currency' => $event->totalAmount->currency(),
        ]);
    }
}
