<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Amqp\Distribution\AmqpDistributedBusConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Configures RabbitMQ as the transport for cross-service communication.
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
