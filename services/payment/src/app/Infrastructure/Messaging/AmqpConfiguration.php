<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Amqp\Distribution\AmqpDistributedBusConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Configures RabbitMQ as the transport for cross-service communication.
 *
 * - The Distributed Bus publishes domain events to RabbitMQ so other
 *   services (Payment, Payout) can consume them.
 * - The same bus receives messages from other services.
 *
 * Local events (within Order Service) still use the in-memory Event Bus.
 */
final class AmqpConfiguration
{
    #[ServiceContext]
    public function distributedBusPublisher(): AmqpDistributedBusConfiguration
    {
        return AmqpDistributedBusConfiguration::createPublisher();
    }

    #[ServiceContext]
    public function distributedBusConsumer(): AmqpDistributedBusConfiguration
    {
        return AmqpDistributedBusConfiguration::createConsumer();
    }
}
