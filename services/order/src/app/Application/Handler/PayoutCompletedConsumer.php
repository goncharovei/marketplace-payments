<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutCompletedMessage;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Psr\Log\LoggerInterface;

final readonly class PayoutCompletedConsumer
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('payout.completed')]
    public function handle(PayoutCompletedMessage $message): void
    {
        $this->logger->info('Payout completed for order', [
            'orderId' => $message->orderId,
            'payoutId' => $message->payoutId,
            'sellerId' => $message->sellerId,
            'amount' => $message->amount,
            'currency' => $message->currency,
        ]);
    }
}
