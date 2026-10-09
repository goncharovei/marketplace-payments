<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutFailedMessage;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Psr\Log\LoggerInterface;

final readonly class PayoutFailedConsumer
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('payout.failed')]
    public function handle(PayoutFailedMessage $message): void
    {
        $this->logger->error('Payout failed for order — manual review required', [
            'orderId' => $message->orderId,
            'payoutId' => $message->payoutId,
            'sellerId' => $message->sellerId,
            'reason' => $message->reason,
        ]);
    }
}
