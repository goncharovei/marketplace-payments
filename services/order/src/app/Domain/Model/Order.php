<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\OrderCancelled;
use App\Domain\Event\OrderCreated;
use App\Domain\Event\OrderPaid;
use App\Domain\Event\OrderRefunded;
use App\Domain\Event\PaymentStarted;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

/**
 * Order aggregate root.
 *
 * Protects business invariants and records domain events
 * for everything that happens inside the aggregate.
 */
#[ORM\Entity]
#[ORM\Table(name: 'orders')]
final class Order
{
    #[ORM\Id]
    #[ORM\Column(type: 'order_id')]
    private OrderId $id;

    #[ORM\Column(type: 'buyer_id')]
    private BuyerId $buyerId;

    #[ORM\Column(type: 'seller_id')]
    private SellerId $sellerId;

    #[ORM\Column(type: 'order_status')]
    private OrderStatus $status;

    #[ORM\Column(type: 'order_item_collection')]
    private OrderItemCollection $items;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /** @var DomainEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        OrderId $id,
        BuyerId $buyerId,
        SellerId $sellerId,
        OrderItemCollection $items,
        OrderStatus $status,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->buyerId = $buyerId;
        $this->sellerId = $sellerId;
        $this->items = $items;
        $this->status = $status;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function place(
        OrderId $id,
        BuyerId $buyerId,
        SellerId $sellerId,
        OrderItemCollection $items,
    ): self {
        if ($items->isEmpty()) {
            throw new DomainException('Cannot place an order with no items.');
        }

        $now = new DateTimeImmutable;

        $order = new self(
            $id,
            $buyerId,
            $sellerId,
            $items,
            OrderStatus::Created,
            $now,
            $now,
        );

        $order->recordEvent(OrderCreated::now(
            $order->id,
            $order->buyerId,
            $order->sellerId,
            $order->totalAmount(),
        ));

        return $order;
    }

    public function startPayment(): void
    {
        if (! $this->status->canBePaid()) {
            throw new DomainException(sprintf(
                'Cannot start payment for order in status "%s".',
                $this->status->value,
            ));
        }

        $this->status = OrderStatus::PaymentProcessing;
        $this->touch();

        $this->recordEvent(PaymentStarted::now($this->id, $this->totalAmount()));
    }

    public function markAsPaid(): void
    {
        if ($this->status !== OrderStatus::PaymentProcessing) {
            throw new DomainException(sprintf(
                'Cannot mark order as paid from status "%s".',
                $this->status->value,
            ));
        }

        $this->status = OrderStatus::Paid;
        $this->touch();

        $this->recordEvent(OrderPaid::now($this->id, $this->totalAmount()));
    }

    public function cancel(string $reason): void
    {
        if (! $this->status->canBeCancelled()) {
            throw new DomainException(sprintf(
                'Cannot cancel order in status "%s".',
                $this->status->value,
            ));
        }

        $wasPaid = $this->status === OrderStatus::Paid;

        $this->status = $wasPaid ? OrderStatus::Refunded : OrderStatus::Cancelled;
        $this->touch();

        if ($wasPaid) {
            $this->recordEvent(OrderRefunded::now($this->id, $this->totalAmount(), $reason));
        } else {
            $this->recordEvent(OrderCancelled::now($this->id, $reason));
        }
    }

    public function id(): OrderId
    {
        return $this->id;
    }

    public function buyerId(): BuyerId
    {
        return $this->buyerId;
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }

    public function items(): OrderItemCollection
    {
        return $this->items;
    }

    public function totalAmount(): Money
    {
        return $this->items->total();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @return DomainEvent[] */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    private function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable;
    }
}
