<?php

declare(strict_types=1);

namespace App\Domain\Model;

use App\Domain\Event\DomainEvent;
use App\Domain\Event\PayoutCompleted;
use App\Domain\Event\PayoutCreated;
use App\Domain\Event\PayoutEscalated;
use App\Domain\Event\PayoutFailed;
use App\Domain\ValueObject\ExternalPayoutId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\PayoutId;
use App\Domain\ValueObject\PlatformFee;
use App\Domain\ValueObject\SellerId;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use DomainException;

/**
 * Payout aggregate root.
 *
 * Lifecycle:
 *   Pending → Processing → Completed
 *                      ↘ Failed → Processing (retry, up to MAX_RETRY_COUNT)
 *                               ↘ OnHold (manual review)
 */
#[ORM\Entity]
#[ORM\Table(name: 'payouts')]
final class Payout
{
    public const MAX_RETRY_COUNT = 5;

    #[ORM\Id]
    #[ORM\Column(type: 'payout_id')]
    private PayoutId $id;

    #[ORM\Column(type: 'seller_id')]
    private SellerId $sellerId;

    #[ORM\Column(type: 'order_id')]
    private OrderId $orderId;

    #[ORM\Column(type: 'money')]
    private Money $amount;

    #[ORM\Column(type: 'money')]
    private Money $platformFee;

    #[ORM\Column(type: 'payout_method')]
    private PayoutMethod $method;

    #[ORM\Column(type: 'payout_status')]
    private PayoutStatus $status;

    #[ORM\Column(type: 'external_payout_id', nullable: true)]
    private ?ExternalPayoutId $externalId;

    #[ORM\Column(type: 'integer')]
    private int $retryCount;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    /** @var DomainEvent[] */
    private array $recordedEvents = [];

    private function __construct(
        PayoutId $id,
        SellerId $sellerId,
        OrderId $orderId,
        Money $amount,
        Money $platformFee,
        PayoutMethod $method,
        PayoutStatus $status,
        ?ExternalPayoutId $externalId,
        int $retryCount,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->sellerId = $sellerId;
        $this->orderId = $orderId;
        $this->amount = $amount;
        $this->platformFee = $platformFee;
        $this->method = $method;
        $this->status = $status;
        $this->externalId = $externalId;
        $this->retryCount = $retryCount;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        PayoutId $id,
        SellerId $sellerId,
        OrderId $orderId,
        Money $orderAmount,
        PlatformFee $fee,
        PayoutMethod $method,
    ): self {
        if ($orderAmount->amount() <= 0) {
            throw new DomainException('Payout order amount must be positive.');
        }

        $platformFeeAmount = $fee->calculate($orderAmount);

        if ($platformFeeAmount->amount() >= $orderAmount->amount()) {
            throw new DomainException('Platform fee cannot consume the entire payout amount.');
        }

        $payoutAmount = $orderAmount->subtract($platformFeeAmount);
        $now = new DateTimeImmutable;

        $payout = new self(
            $id,
            $sellerId,
            $orderId,
            $payoutAmount,
            $platformFeeAmount,
            $method,
            PayoutStatus::PENDING,
            null,
            0,
            $now,
            $now,
        );

        $payout->recordEvent(PayoutCreated::now(
            $payout->id,
            $payout->sellerId,
            $payout->orderId,
            $payout->amount,
            $payout->platformFee,
            $payout->method,
        ));

        return $payout;
    }

    public function start(): void
    {
        if (! $this->status->canBeStarted()) {
            throw new DomainException(sprintf(
                'Cannot start payout in status "%s".',
                $this->status->value,
            ));
        }

        $this->status = PayoutStatus::PROCESSING;
        $this->touch();
    }

    public function complete(ExternalPayoutId $externalId): void
    {
        if (! $this->status->canBeCompleted()) {
            throw new DomainException(sprintf(
                'Cannot complete payout in status "%s".',
                $this->status->value,
            ));
        }

        $this->externalId = $externalId;
        $this->status = PayoutStatus::COMPLETED;
        $this->touch();

        $this->recordEvent(PayoutCompleted::now(
            $this->id,
            $externalId,
            $this->amount,
        ));
    }

    public function fail(string $reason): void
    {
        if (! $this->status->canBeFailed()) {
            throw new DomainException(sprintf(
                'Cannot fail payout in status "%s".',
                $this->status->value,
            ));
        }

        $this->status = PayoutStatus::FAILED;
        $this->touch();

        $this->recordEvent(PayoutFailed::now($this->id, $reason));
    }

    public function retry(): void
    {
        if (! $this->status->canBeRetried()) {
            throw new DomainException(sprintf(
                'Cannot retry payout in status "%s".',
                $this->status->value,
            ));
        }

        if ($this->retryCount >= self::MAX_RETRY_COUNT) {
            throw new DomainException(sprintf(
                'Maximum retry count (%d) reached.',
                self::MAX_RETRY_COUNT,
            ));
        }

        $this->retryCount++;
        $this->status = PayoutStatus::PROCESSING;
        $this->touch();
    }

    public function escalateForManualReview(string $reason): void
    {
        if (! $this->status->canBeEscalated()) {
            throw new DomainException(sprintf(
                'Cannot escalate payout in status "%s".',
                $this->status->value,
            ));
        }

        if ($this->retryCount < self::MAX_RETRY_COUNT) {
            throw new DomainException(sprintf(
                'Cannot escalate before reaching %d retries.',
                self::MAX_RETRY_COUNT,
            ));
        }

        $this->status = PayoutStatus::ON_HOLD;
        $this->touch();

        $this->recordEvent(PayoutEscalated::now($this->id, $reason));
    }

    public function id(): PayoutId
    {
        return $this->id;
    }

    public function sellerId(): SellerId
    {
        return $this->sellerId;
    }

    public function orderId(): OrderId
    {
        return $this->orderId;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function platformFee(): Money
    {
        return $this->platformFee;
    }

    public function method(): PayoutMethod
    {
        return $this->method;
    }

    public function status(): PayoutStatus
    {
        return $this->status;
    }

    public function externalId(): ?ExternalPayoutId
    {
        return $this->externalId;
    }

    public function retryCount(): int
    {
        return $this->retryCount;
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
