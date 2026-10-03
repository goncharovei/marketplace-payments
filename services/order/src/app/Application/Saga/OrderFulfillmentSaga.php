<?php

declare(strict_types=1);

namespace App\Application\Saga;

use App\Domain\Event\OrderCancelled;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderPaid;
use App\Domain\Event\OrderRefunded;
use App\Domain\Event\PaymentStarted;
use App\Domain\Saga\OrderFulfillmentState;
use Ecotone\Modelling\Attribute\EventHandler;
use Ecotone\Modelling\Attribute\Identifier;
use Ecotone\Modelling\Attribute\Saga;

/**
 * Orchestration Saga for the order fulfillment process.
 *
 * State is stored as a plain string (not an enum) because the
 * Document Store serializes Saga state via PHP serialization.
 */
#[Saga]
final class OrderFulfillmentSaga
{
    #[Identifier]
    private string $orderId;

    private string $state = 'started';

    /**
     * Factory method: called only when no Saga exists for the given orderId.
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

    #[EventHandler]
    public function whenOrderPaid(OrderPaid $event): void
    {
        $this->state = OrderFulfillmentState::COMPLETED->value;
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
