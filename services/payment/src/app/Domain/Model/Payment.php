<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\PaymentAuthorized;
use App\Domain\Event\PaymentCaptured;
use App\Domain\Event\PaymentFailed;
use App\Domain\Event\PaymentInitiated;
use App\Domain\Event\PaymentRefunded;
use App\Domain\ValueObject\BuyerId;
use App\Domain\ValueObject\ExternalTransactionId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PaymentId;
use App\Domain\ValueObject\RefundId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

/**
 * Payment aggregate root.
 *
 * Enforces the lifecycle of a payment:
 *   Initiated → Authorized → Captured
 *   Initiated → Failed
 *   Captured → Refunded (full) / PartiallyRefunded
 */
#[ORM\Entity]
#[ORM\Table(name: 'payments')]
final class Payment
{
    #[ORM\Id]
    #[ORM\Column(type: 'payment_id')]
    private PaymentId $id;

    #[ORM\Column(type: 'order_id')]
    private OrderId $orderId;

    #[ORM\Column(type: 'buyer_id')]
    private BuyerId $buyerId;

    #[ORM\Column(type: 'money')]
    private Money $amount;

    #[ORM\Column(type: 'payment_method')]
    private PaymentMethod $method;

    #[ORM\Column(type: 'payment_status')]
    private PaymentStatus $status;

    #[ORM\Column(type: 'external_transaction_id', nullable: true)]
    private ?ExternalTransactionId $externalId;

    #[ORM\Column(type: 'money')]
    private Money $refundedAmount;

    /** @var Refund[] */
    #[ORM\Column(type: 'refund_collection', options: ['jsonb' => true])]
    private array $refunds;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /** @var DomainEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        PaymentId $id,
        OrderId $orderId,
        BuyerId $buyerId,
        Money $amount,
        PaymentMethod $method,
        PaymentStatus $status,
        ?ExternalTransactionId $externalId,
        Money $refundedAmount,
        array $refunds,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->orderId = $orderId;
        $this->buyerId = $buyerId;
        $this->amount = $amount;
        $this->method = $method;
        $this->status = $status;
        $this->externalId = $externalId;
        $this->refundedAmount = $refundedAmount;
        $this->refunds = $refunds;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function initiate(
        PaymentId $id,
        OrderId $orderId,
        BuyerId $buyerId,
        Money $amount,
        PaymentMethod $method,
    ): self {
        if ($amount->amount() <= 0) {
            throw new DomainException('Payment amount must be positive.');
        }

        $now = new DateTimeImmutable;

        $payment = new self(
            $id,
            $orderId,
            $buyerId,
            $amount,
            $method,
            PaymentStatus::INITIATED,
            null,
            Money::zero($amount->currency()),
            [],
            $now,
            $now,
        );

        $payment->recordEvent(PaymentInitiated::now(
            $payment->id,
            $payment->orderId,
            $payment->buyerId,
            $payment->amount,
            $payment->method,
        ));

        return $payment;
    }

    public function authorize(ExternalTransactionId $externalId): void
    {
        if (! $this->status->canBeAuthorized()) {
            throw new DomainException(sprintf(
                'Cannot authorize payment in status "%s".',
                $this->status->value,
            ));
        }

        $this->externalId = $externalId;
        $this->status = PaymentStatus::AUTHORIZED;
        $this->touch();

        $this->recordEvent(PaymentAuthorized::now($this->id, $externalId));
    }

    public function capture(): void
    {
        if (! $this->status->canBeCaptured()) {
            throw new DomainException(sprintf(
                'Cannot capture payment in status "%s".',
                $this->status->value,
            ));
        }

        $this->status = PaymentStatus::CAPTURED;
        $this->touch();

        $this->recordEvent(PaymentCaptured::now($this->id, $this->amount));
    }

    public function fail(string $reason): void
    {
        if ($this->status->isFinal()) {
            throw new DomainException(sprintf(
                'Cannot fail payment in status "%s".',
                $this->status->value,
            ));
        }

        $this->status = PaymentStatus::FAILED;
        $this->touch();

        $this->recordEvent(PaymentFailed::now($this->id, $reason));
    }

    public function refund(Money $amount, string $reason): Refund
    {
        if (! $this->status->canBeRefunded()) {
            throw new DomainException(sprintf(
                'Cannot refund payment in status "%s".',
                $this->status->value,
            ));
        }

        if ($amount->currency() !== $this->amount->currency()) {
            throw new DomainException('Refund currency must match payment currency.');
        }

        $newRefundedTotal = $this->refundedAmount->add($amount);

        if ($newRefundedTotal->amount() > $this->amount->amount()) {
            throw new DomainException('Refund amount cannot exceed the captured amount.');
        }

        $refund = Refund::create(RefundId::generate(), $amount, $reason);

        $this->refunds[] = $refund;
        $this->refundedAmount = $newRefundedTotal;
        $this->status = $newRefundedTotal->equals($this->amount)
            ? PaymentStatus::REFUNDED
            : PaymentStatus::PARTIALLY_REFUNDED;
        $this->touch();

        $this->recordEvent(PaymentRefunded::now($this->id, $refund->id(), $amount, $reason));

        return $refund;
    }

    public function id(): PaymentId
    {
        return $this->id;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function buyerId(): BuyerId
    {
        return $this->buyerId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function refundedAmount(): Money
    {
        return $this->refundedAmount;
    }

    public function method(): PaymentMethod
    {
        return $this->method;
    }

    public function status(): PaymentStatus
    {
        return $this->status;
    }

    public function externalId(): ?ExternalTransactionId
    {
        return $this->externalId;
    }

    /** @return Refund[] */
    public function refunds(): array
    {
        return $this->refunds;
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
