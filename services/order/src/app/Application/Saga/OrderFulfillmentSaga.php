<?php

declare(strict_types=1);

namespace App\Application\Saga;

use App\Application\Dto\PayoutCompletedSignal;
use App\Application\Dto\PayoutFailedSignal;
use App\Application\Dto\PayoutRequested;
use App\Domain\Event\OrderCancelled;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderPaid;
use App\Domain\Event\OrderRefunded;
use App\Domain\Event\PaymentStarted;
use App\Domain\Saga\OrderFulfillmentState;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;
use Ecotone\Modelling\EventBus;

/**
 * Orchestration Saga for the order fulfillment process.
 *
 * The Saga is the only component that knows the whole business process.
 * It reacts to domain events and drives the flow forward by sending
 * commands to other services via the DistributedBus.
 *
 * State is stored as a plain string (Ecotone serializes Saga state).
 */
#[Saga]
final class OrderFulfillmentSaga
{
    #[Identifier]
    private string $orderId;

    private string $state = 'started';

    /**
     * Factory: called only when no Saga exists for the given orderId.
     */
    #[EventHandler]
    public static function startWhen(OrderCreated $event): self
    {
        $saga = new self;
        $saga->orderId = $event->orderId->toString();
        $saga->state = OrderFulfillmentState::STARTED->value;

        return $saga;
    }

    #[EventHandler]
    public function whenPaymentStarted(PaymentStarted $event): void
    {
        $this->state = OrderFulfillmentState::AWAITING_PAYMENT->value;
    }

    /**
     * After the order is Paid, the Saga raises a PayoutRequested event.
     * PayoutRequestedNotifier picks it up and forwards a distributed
     * command to the Payout Service. This keeps the Saga decoupled from
     * routing keys and target service names.
     */
    #[EventHandler]
    public function whenOrderPaid(OrderPaid $event, EventBus $eventBus): void
    {
        $this->state = OrderFulfillmentState::PAID->value;

        $eventBus->publish(PayoutRequested::now(
            orderId: $event->orderId->toString(),
            sellerId: $event->sellerId->toString(),
            amount: $event->amount->amount(),
            currency: $event->amount->currency(),
        ));
    }

    /**
     * Reached only after Payout Service confirms the transfer.
     */
    #[EventHandler]
    public function whenPayoutCompleted(PayoutCompletedSignal $signal): void
    {
        $this->state = OrderFulfillmentState::COMPLETED->value;
    }

    /**
     * Compensation path: the payment was captured, but the payout failed.
     * The order stays in Paid — money is not returned to the buyer.
     * Manual review is required.
     */
    #[EventHandler]
    public function whenPayoutFailed(PayoutFailedSignal $signal): void
    {
        $this->state = OrderFulfillmentState::PAYOUT_FAILED->value;
    }

    #[EventHandler]
    public function whenOrderCancelled(OrderCancelled $event): void
    {
        $this->state = OrderFulfillmentState::CANCELLED->value;
    }

    #[EventHandler]
    public function whenOrderRefunded(OrderRefunded $event): void
    {
        $this->state = OrderFulfillmentState::CANCELLED->value;
    }

    public function state(): string
    {
        return $this->state;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }
}
