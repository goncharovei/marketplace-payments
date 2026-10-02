<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Dbal\Configuration\DbalConfiguration as EcotoneDbalConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Tells Ecotone to use the DBAL connection configured in Laravel
 * (PostgreSQL via Laravel Doctrine) instead of creating its own.
 */
final class DbalConfiguration
{
    #[ServiceContext]
    public function dbal(): EcotoneDbalConfiguration
    {
        return EcotoneDbalConfiguration::createWithDefaults()
            ->withTransactionOnCommandBus(true)
            ->withTransactionOnAsynchronousEndpoints(true);
    }
}
