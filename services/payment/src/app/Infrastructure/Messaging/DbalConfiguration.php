<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

use App\Application\Saga\OrderFulfillmentSaga;
use Ecotone\Dbal\Configuration\DbalConfiguration as EcotoneDbalConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;

/**
 * Configures Doctrine DBAL integration for Ecotone.
 *
 * - Transactions for Command Bus and async endpoints.
 * - Document Store for Saga state persistence.
 *
 */
final class DbalConfiguration
{
    #[ServiceContext]
    public function dbal(): EcotoneDbalConfiguration
    {
        return EcotoneDbalConfiguration::createWithDefaults()
            ->withTransactionOnCommandBus(true)
            ->withTransactionOnAsynchronousEndpoints(true)
            ->withDocumentStore(
                isDocumentStoreEnabled: true,
                enableDocumentStoreStandardRepository: true
            );
    }
}
