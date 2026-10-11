<?php

declare(strict_types=1);

namespace App\Application\Handler;

use App\Application\Dto\PayoutFailedMessage;
use App\Application\Dto\PayoutFailedSignal;
use Ecotone\Modelling\Attribute\Distributed;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\EventBus;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class PayoutFailedConsumer
{
    public function __construct(
        private EventBus $eventBus,
        private LoggerInterface $logger,
    ) {}

    #[Distributed]
    #[EventHandler('payout.failed')]
    public function handle(PayoutFailedMessage $message): void
    {
        $this->logger->error('Payout failed — manual review required', [
            'orderId' => $message->orderId,
            'payoutId' => $message->payoutId,
            'reason' => $message->reason,
        ]);

        try {
            $this->eventBus->publish(PayoutFailedSignal::now(
                orderId: $message->orderId,
                reason: $message->reason,
            ));
        } catch (Throwable $e) {
            // No Saga exists for this order — the failure is logged,
            // manual review happens via alerting on the error log above.
            $this->logger->warning('PayoutFailedSignal ignored: no matching Saga', [
                'orderId' => $message->orderId,
                'reason' => $e->getMessage(),
            ]);
        }
    }
}
