<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use Ecotone\Dbal\Configuration\DbalConfiguration as EcotoneDbalConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Configures Doctrine DBAL integration for Ecotone.
 *
 * Unlike the Order Service, the Payout Service does not host any Saga,
 * so the Document Store is not enabled here.
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
